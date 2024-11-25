// function showModal(title, message, onOk) {
//   // Create the modal elements
//   const modalOverlay = document.createElement("div");
//   modalOverlay.className = "modal-overlay";

//   const modal = document.createElement("div");
//   modal.className = "modal";

//   const modalContent = document.createElement("div");
//   modalContent.className = "modal-content";

//   const modalHeader = document.createElement("div");
//   modalHeader.className = "modal-header";
//   modalHeader.innerHTML = `<h2 style="color: green;">${title}</h2>`;

//   const modalBody = document.createElement("div");
//   modalBody.className = "modal-body";
//   modalBody.innerHTML = `<p>${message}</p>`;

//   const modalFooter = document.createElement("div");
//   modalFooter.className = "modal-footer";

//   const okButton = document.createElement("button");
//   okButton.innerText = "OK";
//   okButton.style.backgroundColor = "#0073aa";
//   okButton.style.border = "none"; // Remove border
//   okButton.style.color = "white"; // Optional: Set text color for better contrast
//   okButton.style.padding = "10px 20px"; // Optional: Add padding for better appearance
//   okButton.style.cursor = "pointer"; // Optional: Change cursor on hover
//   okButton.style.borderRadius = "5px";
//   okButton.onclick = function () {
//     document.body.removeChild(modalOverlay); // Remove the modal overlay
//     document.body.removeChild(modal); // Remove the modal
//     if (onOk) onOk(); // Call the onOk callback
//   };

//   modalFooter.appendChild(okButton);
//   modalContent.appendChild(modalHeader);
//   modalContent.appendChild(modalBody);
//   modalContent.appendChild(modalFooter);
//   modal.appendChild(modalContent);

//   document.body.appendChild(modalOverlay);
//   document.body.appendChild(modal);

//   // Optional: Style the modal overlay (semi-transparent background)
//   modalOverlay.style.position = "fixed";
//   modalOverlay.style.top = "0";
//   modalOverlay.style.left = "0";
//   modalOverlay.style.width = "100%";
//   modalOverlay.style.height = "100%";
//   modalOverlay.style.backgroundColor = "rgba(0, 0, 0, 0.5)";
//   modalOverlay.style.zIndex = "999"; // Ensure it is above other elements

//   // Optional: Style the modal
//   modal.style.position = "fixed";
//   modal.style.zIndex = "1000"; // Ensure the modal is above the overlay
//   modal.style.display = "flex";
//   modal.style.alignItems = "center";
//   modal.style.justifyContent = "center";
//   modal.style.top = "0";
//   modal.style.left = "0";
//   modal.style.width = "100%";
//   modal.style.height = "100%";

//   // Style the modal content
//   modalContent.style.backgroundColor = "white";
//   modalContent.style.padding = "20px";
//   modalContent.style.borderRadius = "5px";
//   modalContent.style.boxShadow = "0 2px 10px rgba(0, 0, 0, 0.1)";
//   modalContent.style.width = "400px";
//   modalContent.style.maxWidth = "90%";
// }

// Function to upload compressed image.
async function uploadCompressedSingleImage(validCompressedImages) {
  return new Promise(async (resolve, reject) => {
    try {
      // if (filename) {
      const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;

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
          showModal(
            "Optimization succeeded!",
            "Your images have been optimized successfully.",
            function () {
              // Refresh the page when OK is clicked
              location.reload();
            }
          );
          resolve(); // Upload succeeded, resolve the promise
        } else {
          reject(); // Upload failed, reject the promise
        }
      }
    } catch (err) {
      //console.log("Err : uploadCompressedImage()");
      reject(err); // Handle errors by rejecting the promise.
    }
  });
}

// Function to download an image and convert it to a Blob
async function downloadSnigleImage(url) {
  return fetch(url)
    .then((response) => response.blob())
    .then((blob) => {
      const fileName = url.substring(url.lastIndexOf("/") + 1);
      return new File([blob], fileName, { type: blob.type });
    });
}

// Function to compress an image (already defined in your code)
async function compressSingleImage(file, item) {
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
          console.log(outputFormat);
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
function wpovenSingleImageOptimization(data, inbackground) {
  const allImageSizes = [];
  // Gather all image sizes, including original and resized versions
  if (data.images.metadata !== false || data.images.metadata !== "") {
    //  Gather all image sizes, including original and resized versions
    const item = data.images; // Reference the images object

    // Original image
    allImageSizes.push({
      url: item.url,
      key: "original",
      label: data.compression_label,
      type: data.type,
      post_id: item.post_id,
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
        post_id: item.post_id,
        file: sizeData.file,
        width: sizeData.width,
        height: sizeData.height,
      });
    }

    // Compress each image and collect the results
    const compressPromises = allImageSizes.map(async (imageItem) => {
      return await downloadSnigleImage(imageItem.url)
        .then((file) => compressSingleImage(file, imageItem))
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
          uploadCompressedSingleImage(compressedImagesData)
            .then((data) => {
              //
            })
            .catch((err) => {
              console.log("Error uploading compressed images:", err);
            });
        }
      })
      .catch((error) => {
        console.error("Error during image compression:", error);
      });

    // After all images are compressed, send them in a single request
  }
}

async function runOptimization(postId) {
  const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
  if (confirm("Are you sure you want to optimize this image?")) {
    // Perform an AJAX call to optimize the image or trigger your logic

    const formData = new FormData();
    formData.append("action", "wpoven_optimize_single_image");
    formData.append("security", ajax_nonce);
    formData.append("post_id", postId);

    const response = await fetch(myPluginUrl.wpoven_ajax_url, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    });

    if (response.ok) {
      const data = await response.json();
      if (data.status === "ok") {
        if (data.images) {
          const inbackground = false;
          wpovenSingleImageOptimization(data, inbackground);
        }
      }
    }
  }
}
