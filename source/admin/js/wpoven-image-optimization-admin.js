(function ($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  // This function runs when the DOM is fully loaded.
  document.addEventListener("DOMContentLoaded", function () {
    var liElement = document.querySelector("li.bulk-optimization");
    if (liElement) {
      var firstAElement = liElement.querySelector("a");
      if (firstAElement) {
        firstAElement.remove();
      }
    }
    // Remove the top-level menu item for the plugin.
    var element = document.querySelector(
      "li.toplevel_page_wpoven-image-optimization"
    );
    if (element) {
      element.remove();
    }

    //remove extra menu title
    const menuItems = document.querySelectorAll("li#toplevel_page_wpoven");
    const menuArray = Array.from(menuItems);
    for (let i = 1; i < menuArray.length; i++) {
      menuArray[i].remove();
    }

    var $divide1 = document.querySelector("div#divide-divide_1");
    if ($divide1) {
      $divide1.style.display = "none";
    }

    var $divide2 = document.querySelector("div#divide-divide_2");
    if ($divide2) {
      $divide2.style.display = "none";
    }
  });

  // Function to upload compressed image.
  async function uploadCompressedImage(validCompressedImages) {
    return new Promise(async (resolve, reject) => {
      try {
        // if (filename) {
        const ajax_nonce =
          document.getElementById("wpoven-ajax-nonce").innerText;

        const formData = new FormData();
        formData.append("action", "wpoven_put_image_in_dir");
        formData.append("security", ajax_nonce);

        validCompressedImages.forEach((imageData, index) => {
          formData.append(`image_${index}`, imageData.blob, imageData.filename);
          formData.append(`file_${index}`, imageData.file);
          formData.append(`type_${index}`, imageData.type);
          formData.append(`post_id_${index}`, imageData.post_id);
          formData.append(`key_${index}`, imageData.key);
        });

        const response = await fetch(myPluginUrl.wpoven_ajax_url, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        });

        if (response.ok) {
          const data = await response.json();
          if (data.status === "ok") {
            resolve(data); // Upload succeeded, resolve the promise.
          } else {
            reject(data); // Upload failed, reject the promise.
          }
        }
        // }
      } catch (err) {
        reject(err); // Handle errors by rejecting the promise.
      }
    });
  }

  // Function to download an image and convert it to a Blob.
  async function downloadImage(url) {
    return fetch(url)
      .then((response) => response.blob())
      .then((blob) => {
        const fileName = url.substring(url.lastIndexOf("/") + 1);
        return new File([blob], fileName, { type: blob.type });
      });
  }

  // Function to compress an image.
  async function compressImage(file, item) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();

      reader.onload = (event) => {
        const img = new Image();
        img.src = event.target.result;

        img.onload = () => {
          const canvas = document.createElement("canvas");
          const ctx = canvas.getContext("2d");

          canvas.width = item.width;
          canvas.height = item.height;

          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

          if (item.type === "avif") {
            // Need to Implement.
          } else {
            const outputFormat = "image/" + item.type;
            //console.log(outputFormat);
            canvas.toBlob(
              (blob) => {
                resolve({
                  blob: blob,
                  filename: `${item.file}.${item.type}`,
                  file: item.file,
                  type: item.type,
                  post_id: item.post_id,
                  key: item.key,
                });
              },
              outputFormat,
              item.label
            );
          }
        };

        img.onerror = () => {
          reject("Error loading image");
        };
      };

      reader.onerror = () => {
        reject("Error reading file");
      };

      reader.readAsDataURL(file);
    });
  }

  // Main function for image optimization.
  function wpovenImageOptimization(data, inbackground) {
    const allImageSizes = [];
    // Gather all image sizes, including original and resized versions
    if (data.images.metadata !== false || data.images.metadata !== "") {
      data.images.forEach((item, index) => {
        //console.log(data.images[index].post_id);
        // Original image
        allImageSizes.push({
          url: item.url,
          key: "original",
          label: data.compression_label,
          type: data.type,
          post_id: data.images[index].post_id,
          file: item.metadata.file,
          width: item.metadata.width,
          height: item.metadata.height,
        });

        // Add resized versions
        for (let size in item.metadata.sizes) {
          const sizeData = item.metadata.sizes[size];
          allImageSizes.push({
            url: item.url,
            key: size,
            label: data.compression_label,
            type: data.type,
            post_id: data.images[index].post_id,
            file: sizeData.file,
            width: sizeData.width,
            height: sizeData.height,
          });
        }
      });

      // Compress each image and collect the results
      const compressPromises = allImageSizes.map(async (imageItem) => {
        return await downloadImage(imageItem.url)
          .then((file) => compressImage(file, imageItem))
          .then((compressedData) => {
            return compressedData; // Return compressed image data
          })
          .catch((err) => {
            //  console.log("Error compressing image:", err);
          });
      });
      // Wait for all images to be compressed
      Promise.all(compressPromises)
        .then((compressedImagesData) => {
          // Log the entire array of compressed images data
          if (compressedImagesData.length > 0) {
            uploadCompressedImage(compressedImagesData)
              .then((data) => {
                // Run the optimization after all images are handled
                if (data.status === "ok") {
                  showModal();
                  if (inbackground === false) {
                    //setTimeout(() => {
                    wpovenCallOptimization();
                    // }, 10000); // 0.5 seconds delay
                  }
                }
              })
              .catch((err) => {
                console.log("Error uploading compressed images:", err);
              });
          }
        })
        .catch((error) => {
          console.error("Error during image compression:", error);
        });
    }
  }

  // Function to call optimization after images are uploaded.
  async function wpovenCallOptimization() {
    try {
      const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
      const response = await fetch(myPluginUrl.wpoven_ajax_url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
        },
        body: `action=wpoven_call_optimization&security=${ajax_nonce}`,
        credentials: "same-origin",
        timeout: 20000,
      });
      if (response.ok) {
        const data = await response.json();
        if (data.status === "ok") {
          // console.log(data);
          if (Array.isArray(data.images) && data.images.length > 0) {
            const inbackground = false;
            wpovenImageOptimization(data, inbackground);
          } else {
            setTimeout(() => hideModal(), 5000);
            var sizeChart = document.getElementById(
              "optimizationImageSizeChart"
            );
            var imageOptimizationChart = document.getElementById(
              "optimizationImageChart"
            );

            if (sizeChart && imageOptimizationChart) {
              wpovenGetDataToShowGraph(sizeChart, imageOptimizationChart);
            }
          }
        } else {
          setTimeout(() => hideModal(data), 5000);
        }
      }
    } catch (err) {
      console.log("Err :" + err);
    }
  }

  // Function to call optimization after an image is uploaded.
  async function wpovenCallOptimizationAfterUploadImage() {
    try {
      const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
      if (!ajax_nonce) {
        throw new Error("Nonce element not found in the DOM.");
      }

      const response = await fetch(myPluginUrl.wpoven_ajax_url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
        },
        body: `action=wpoven_call_optimization_after_upload_image&security=${ajax_nonce}`,
        credentials: "same-origin",
        timeout: 10000,
      });

      if (response.ok) {
        const data = await response.json();
        if (data.status === "ok") {
          if (data.images !== null) {
            const inbackground = true;
            wpovenImageOptimization(data, inbackground);
          }
        }
      }
    } catch (err) {
      console.error(err);
    }
  }

  let optimizationImageSizeChartInstance = null;
  let optimizationImageChartInstance = null;

  // Function to get data for showing the optimization graphs.
  async function wpovenGetDataToShowGraph(sizeChart, imageOptimizationChart) {
    try {
      const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
      if (!ajax_nonce) {
        throw new Error("Nonce element not found in the DOM.");
      }

      const response = await fetch(myPluginUrl.wpoven_ajax_url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
        },
        body: `action=wpoven_get_data_to_show_graph&security=${ajax_nonce}`,
        credentials: "same-origin",
        timeout: 10000,
      });

      if (response.ok) {
        const data = await response.json();
        if (data.status === "ok") {
          // Destroy the previous instance of the size chart if it exists
          if (optimizationImageSizeChartInstance) {
            optimizationImageSizeChartInstance.destroy();
          }

          // Create a new chart
          var ctxSizeChart = sizeChart.getContext("2d");
          optimizationImageSizeChartInstance = new Chart(ctxSizeChart, {
            type: "pie",
            data: {
              labels: ["Original size(MB)", "Optimized size(MB)"],
              datasets: [
                {
                  data: [
                    data["total_original_size"],
                    data["total_compressed_size"],
                  ],
                  backgroundColor: ["#4CAF50", "#4c76c9"],
                },
              ],
            },
          });

          // Destroy the previous instance of the image optimization chart if it exists
          if (optimizationImageChartInstance) {
            optimizationImageChartInstance.destroy();
          }

          // Create a new chart
          var ctxImageChart = imageOptimizationChart.getContext("2d");
          optimizationImageChartInstance = new Chart(ctxImageChart, {
            type: "doughnut",
            data: {
              labels: ["Optimized", "Not Optimized", "Error"],
              datasets: [
                {
                  data: [
                    data["optimized"],
                    data["not_optimized"],
                    data["error"],
                  ],
                  backgroundColor: ["#4CAF50", "#4c76c9", "#F44336"],
                },
              ],
            },
          });
        }
      }
    } catch (err) {
      console.error(err);
    }
  }

  async function showModal() {
    const response = await fetch(myPluginUrl.wpoven_ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=wpoven_get_data_for_show_hide_modal&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      if (data.status === "ok") {
        const line1 = document.getElementById("line1");
        const line2 = document.getElementById("line2");
        const line3 = document.getElementById("line3");
        const line4 = document.getElementById("line4");
        const imageOptimizationCard = document.getElementById("image-optimization-modal");

        if (line1) {
          line1.textContent = data["success_msg"] || "";
        }
        if (line2) {
          line2.textContent = data["type"] || "";
        }
        if (line3) {
          line3.textContent = data["total_optimized_images"] || "0";
        }
        if (line4) {
          line4.textContent = data["total_unoptimized_images"] || "0";
        }
        if (imageOptimizationCard) {
          imageOptimizationCard.style.display = "flex";
        }
      }
    }
  }

  async function hideModal() {
    const response = await fetch(myPluginUrl.wpoven_ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=wpoven_get_data_for_show_hide_modal&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      // console.log(data);
      if (data.status === "ok") {
        const line1 = document.getElementById("line1");
        const line2 = document.getElementById("line2");
        const line3 = document.getElementById("line3");
        const line4 = document.getElementById("line4");
        const imageOptimizationCard = document.getElementById("image-optimization-modal");

        if (line1) {
          line1.textContent = data["success_msg"] || "";
        }
        if (line2) {
          line2.textContent = data["type"] || "";
        }
        if (line3) {
          line3.textContent = data["total_optimized_images"] || "0";
        }
        if (line4) {
          line4.textContent = data["total_unoptimized_images"] || "0";
        }
        if (imageOptimizationCard) {
          imageOptimizationCard.style.display = "none";
        }
      }
    }
  }

  setInterval(wpovenCallOptimizationAfterUploadImage, 2000);

  // Function for re-optimized images.
  async function wpovenImageOptimizationAgain() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const response = await fetch(myPluginUrl.wpoven_ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=wpoven_image_optimization_again&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      if (data.status === "ok") {
        showModal();
        wpovenCallOptimization();
      }
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    var sizeChart = document.getElementById("optimizationImageSizeChart");
    var imageOptimizationChart = document.getElementById(
      "optimizationImageChart"
    );

    if (sizeChart && imageOptimizationChart) {
      wpovenGetDataToShowGraph(sizeChart, imageOptimizationChart);
    }
  });

  document.addEventListener("DOMContentLoaded", function (event) {
    document.querySelectorAll('th[scope="row"]').forEach(function (th) {
      // Check if the <th> is empty (i.e., no text or inner elements)
      if (!th.innerHTML.trim()) {
        th.remove(); // Remove the <th> if it's empty
      }
    });

    var checkbox = document.getElementById(
      "wpoven-image-optimization_media_media_0"
    );
    var bulkOptimizedButton = document.querySelector(
      '[id="wpoven-bulk-action"]'
    );
    var bulkOptimizedlabel = document.querySelector(
      '[id="wpoven-bulk-action"]'
    );

    var bulkOptimizedButtonAgain = document.querySelector(
      '[id="wpoven-bulk-action-again"]'
    );
    var bulkOptimizedlabelAgain = document.querySelector(
      '[id="wpoven-bulk-action-again"]'
    );

    function updateButtonState() {
      if (checkbox) {
        checkbox.value = checkbox.checked ? "1" : "0";
        if (checkbox.value === "1") {
          bulkOptimizedlabel.disabled = false;
          bulkOptimizedlabel.style.cursor = "pointer"; // Enable button if checkbox is checked, otherwise disable
          bulkOptimizedlabelAgain.disabled = false;
          bulkOptimizedlabelAgain.style.cursor = "pointer";
        } else {
          bulkOptimizedlabel.disabled = true;
          bulkOptimizedlabel.style.cursor = "not-allowed";
          bulkOptimizedlabelAgain.disabled = true;
          bulkOptimizedlabelAgain.style.cursor = "not-allowed";
        }
      }
    }
    updateButtonState();

    if (checkbox) {
      checkbox.addEventListener("change", updateButtonState);
    }

    if (bulkOptimizedButton) {
      bulkOptimizedButton.addEventListener("click", function (event) {
        event.stopPropagation();
        if (!bulkOptimizedlabel.disabled) {
          showModal();
          wpovenCallOptimization();
        }
      });
    }

    if (bulkOptimizedButtonAgain) {
      bulkOptimizedButtonAgain.addEventListener("click", function (event) {
        event.stopPropagation();
        if (!bulkOptimizedlabelAgain.disabled) {
          if (confirm("Are you want to aptimized images again?")) {
            wpovenImageOptimizationAgain();
          }
        }
      });
    }
  });
})(jQuery);
