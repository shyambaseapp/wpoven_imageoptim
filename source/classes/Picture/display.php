<?php

namespace WPOven\Picture;

class Display
{
    public function __construct()
    {
        // Constructor can be used if dependency injection or other initializations are needed.
    }

    public function start_content_process()
    {
        $option = get_option(WPOVEN_IMAGE_OPTIMIZATION_SLUG);
        $nextgen_format = isset($option['next-gen-format']) ? $option['next-gen-format'] : 'off';
        $nextgen_format_method = isset($option['nextgen_format_method']) ? $option['nextgen_format_method'] : 'rewrite_rules';

        if ($nextgen_format == 'off') {
            return;
        }

        if ($nextgen_format_method == 'rewrite_rules') {
            return;
        }

        ob_start([$this, 'maybe_process_buffer']);
    }

    public function maybe_process_buffer($buffer)
    {
        if (! $this->is_html($buffer)) {
            return $buffer;
        }

        if (strlen($buffer) <= 255) {
            return $buffer;
        }

        $buffer = $this->process_content($buffer);

        return (string) apply_filters('wpoven_buffer', $buffer);
    }

    public function process_content($content)
    {
        $html_no_picture_tags = $this->remove_picture_tags($content);
        $images = $this->get_images($html_no_picture_tags);

        if (! $images) {
            return $content;
        }

        foreach ($images as $image) {
            $tag = $this->build_picture_tag($image);
            $content = str_replace($image['tag'], $tag, $content);
        }

        return $content;
    }

    private function remove_picture_tags($html)
    {
        return preg_replace('#<picture[^>]*>.*?<\/picture\s*>#mis', '', $html) ?: $html;
    }

    protected function build_picture_tag($image)
    {
        $attributes = array_diff_key($image['attributes'], [
            'alt' => '',
            'height' => '',
            'width' => '',
            'data-lazy-src' => '',
            'data-src' => '',
            'src' => '',
            'data-lazy-srcset' => '',
            'data-srcset' => '',
            'srcset' => '',
            'data-lazy-sizes' => '',
            'data-sizes' => '',
            'sizes' => ''
        ]);

        $attributes = apply_filters('wpoven_picture_attributes', $attributes, $image);

        if (!empty($image['attributes']['class']) && strpos($image['attributes']['class'], 'wp-block-cover__image-background') !== false) {
            unset($attributes['style'], $attributes['class'], $attributes['data-object-fit'], $attributes['data-object-position']);
        }

        $output = '<picture' . $this->build_attributes($attributes) . ">\n";
        $output .= apply_filters('wpoven_additional_source_tags', '', $image);
        $output .= $this->build_source_tag($image);
        $output .= $this->build_img_tag($image);
        $output .= "</picture>\n";

        return $output;
    }

    protected function build_source_tag($image)
    {
        $source = '';

        $option = get_option(WPOVEN_IMAGE_OPTIMIZATION_SLUG);
        $next_gen_type = isset($option['next-gen-format']) ? $option['next-gen-format'] : null;
        // Create optimized image URLs by adding .avif and .webp
        if ($next_gen_type != 'off') {
            foreach ([$next_gen_type] as $image_type) {
                $attributes = $this->build_source_attributes($image, $image_type);

                if (empty($attributes)) {
                    continue;
                }

                $source .= '<source' . $this->build_attributes($attributes) . "/>\n";
            }
        }

        return $source;
    }

    protected function build_source_attributes(array $image, string $image_type): array
    {
        $mime_type = $image_type === 'webp' ? 'image/webp' : 'image/avif';
        $url_key = $image_type . '_url';
        $srcset_source = !empty($image['srcset_attribute']) ? $image['srcset_attribute'] : 'srcset';
        $attributes = [
            'type' => $mime_type,
            $srcset_source => [],
        ];

        if (!empty($image['srcset'])) {
            foreach ($image['srcset'] as $srcset) {
                if (!empty($srcset[$url_key])) {
                    $attributes[$srcset_source][] = $srcset[$url_key] . ' ' . $srcset['descriptor'];
                }
            }
        }

        if (empty($attributes[$srcset_source]) && !empty($image['src'][$url_key])) {
            $attributes[$srcset_source][] = $image['src'][$url_key];
        }

        $attributes[$srcset_source] = implode(', ', $attributes[$srcset_source]);

        foreach (['data-lazy-srcset', 'data-srcset', 'srcset'] as $srcset_attr) {
            if (!empty($image['attributes'][$srcset_attr]) && $srcset_attr !== $srcset_source) {
                $attributes[$srcset_attr] = $image['attributes'][$srcset_attr];
            }
        }

        if ('srcset' !== $srcset_source && empty($attributes['srcset']) && !empty($image['attributes']['src'])) {
            $attributes['srcset'] = $image['attributes']['src'];
        }

        foreach (['data-lazy-sizes', 'data-sizes', 'sizes'] as $sizes_attr) {
            if (!empty($image['attributes'][$sizes_attr])) {
                $attributes[$sizes_attr] = $image['attributes'][$sizes_attr];
            }
        }

        return apply_filters('wpoven_picture_source_attributes', $attributes, $image);
    }

    protected function build_img_tag($image)
    {
        $to_remove = [
            'id' => '',
            'title' => '',
        ];

        if (!empty($image['attributes']['class']) && strpos($image['attributes']['class'], 'wp-block-cover__image-background') !== false) {
            $attributes = array_diff_key($image['attributes'], $to_remove);
        } else {
            $attributes = array_diff_key($image['attributes'], array_merge($to_remove, [
                'class' => '',
                'style' => '',
            ]));
        }

        $attributes = apply_filters('wpoven_picture_img_attributes', $attributes, $image);

        return '<img' . $this->build_attributes($attributes) . "/>\n";
    }

    protected function build_attributes($attributes)
    {
        $out = '';

        foreach ($attributes as $attribute => $value) {
            $out .= ' ' . $attribute . '="' . esc_attr($value) . '"';
        }

        return $out;
    }

    protected function get_images($content)
    {
        $content = preg_replace('/<!--(.*)-->/Uis', '', $content);

        if (!preg_match_all('/<img\s.*>/isU', $content, $matches)) {
            return [];
        }

        $images = array_map([$this, 'process_image'], $matches[0]);

        return array_filter(apply_filters('wpoven_webp_picture_images_to_display', $images, $content));
    }

    protected function process_image($image)
    {
        $atts_pattern = '/(?<name>[^\s"\']+)\s*=\s*(["\'])\s*(?<value>.*?)\s*\2/s';

        if (!preg_match_all($atts_pattern, $image, $tmp_attributes, PREG_SET_ORDER)) {
            return false;
        }

        $attributes = [];

        foreach ($tmp_attributes as $attribute) {
            $attributes[$attribute['name']] = $attribute['value'];
        }

        if (!empty($attributes['class']) && strpos($attributes['class'], 'wpoven-no-webp') !== false) {
            return false;
        }

        $src_source = false;

        foreach (['data-lazy-src', 'data-src', 'src'] as $src_attr) {
            if (!empty($attributes[$src_attr])) {
                $src_source = $src_attr;
                break;
            }
        }

        if (!$src_source) {
            return false;
        }

        $src = [];
        if (preg_match('@^(?<src>(?:(?:https?:)?//|/).+\.(?<extension>jpg|jpeg|png|gif))(?<query>\?.*)?$@i', $attributes[$src_source], $src)) {
            $data = [
                'tag' => $image,
                'attributes' => $attributes,
                'src_attribute' => $src_source,
                'src' => ['url' => $attributes[$src_source]],
                'srcset_attribute' => false,
                'srcset' => [],
            ];

            foreach ($this->get_nextgen_image_data_set($src) as $key => $value) {
                $data['src'][$key] = $value;
            }

            if (!empty($attributes['srcset'])) {
                $data['srcset_attribute'] = 'srcset';
                $srcset = explode(',', $attributes['srcset']);

                foreach ($srcset as $srcs) {
                    $srcs = preg_split('/\s+/', trim($srcs));

                    if (count($srcs) > 2) {
                        $descriptor = array_pop($srcs);
                        $srcs = [implode(' ', $srcs), $descriptor];
                    }

                    if (empty($srcs[1])) {
                        $srcs[1] = '1x';
                    }

                    if (preg_match('@^(?<src>(?:https?:)?//.+\.(?<extension>jpg|jpeg|png|gif))(?<query>\?.*)?$@i', $srcs[0], $src)) {
                        $srcset_data = [
                            'url' => $srcs[0],
                            'descriptor' => $srcs[1],
                        ];

                        foreach ($this->get_nextgen_image_data_set($src) as $key => $value) {
                            $srcset_data[$key] = $value;
                        }

                        $data['srcset'][] = $srcset_data;
                    }
                }
            }

            return $data;
        }

        return false;
    }

    private function get_nextgen_image_data_set(array $src)
    {
        $base_url = get_site_url(); // Base URL of your site
        $out = [
            'url' => $src['src'],
            'extension' => strtolower($src['extension']),
        ];

        // Parse the URL to get the path
        $url_path = wp_parse_url($src['src'], PHP_URL_PATH);
        // Extract the path info (directory and filename) from the URL
        $path_info = pathinfo($url_path);
        $base_filename = basename($src['src']); // Example: Screenshot-from-2024-08-09-11-54-24-1024x606.png
        $path_parts = explode('/', trim($path_info['dirname'], '/'));
        // Assuming the directory structure is like 'uploads/2024/09'
        $year = $path_parts[count($path_parts) - 2]; // Extract year
        $month = $path_parts[count($path_parts) - 1]; // Extract month
        // Construct the optimized directory path dynamically with year and month
        $optimized_dir = "/wp-content/uploads/wpoven_optimized_images/{$year}/{$month}/"; // The optimized images folder with year and month

        $option = get_option(WPOVEN_IMAGE_OPTIMIZATION_SLUG);
        $next_gen_type = isset($option['next-gen-format']) ? $option['next-gen-format'] : null;
        // Create optimized image URLs by adding .avif and .webp
        if ($next_gen_type != 'off') {
            foreach ([$next_gen_type] as $image_type) {
                if (!empty($src['query'])) {
                    // Add .avif or .webp after the original extension
                    $out[$image_type . '_url'] = preg_replace(
                        '@(\.[^./]+)$@',
                        '$1.' . $image_type . $src['query'],
                        $base_url . $optimized_dir . $base_filename
                    );
                } else {
                    $out[$image_type . '_url'] = preg_replace(
                        '@(\.[^./]+)$@',
                        '$1.' . $image_type,
                        $base_url . $optimized_dir . $base_filename
                    );
                }
            }
        }
        return $out;
    }



    protected function is_html($content)
    {
        return str_contains((string) $content, '<') && str_contains((string) $content, '>');
    }
}
