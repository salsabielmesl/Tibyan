<?php include('comm/header.php');?>

<!-- Custom styling to match your dashboard theme -->
<style>
    /* Card wrapper to match your application's clean design */
    .form-card-wrapper {
        background: #ffffff;
        border-radius: 12px;
        padding: 40px 35px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Form control layout refinements */
    .form-card-wrapper label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    .form-card-wrapper .form-control {
        border-radius: 6px;
        padding: 10px 15px;
        border: 1px solid #ced4da;
    }

    .form-card-wrapper .form-control:focus {
        border-color: #009CFF;
        box-shadow: 0 0 0 0.2rem rgba(0, 156, 255, 0.25);
    }

    /* Centered preview image styling */
    .preview-box {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 10px;
        background-color: #f8f9fa;
        max-width: 140px;
    }
</style>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>

    <?php include('comm/sidebar.php')?>

        <div class="content">
          <?php require('comm/navbar.php')?>

            <div class="container-fluid pt-4 px-4">
                <div class="row min-vh-100 bg-light rounded align-items-center justify-content-center mx-0">
                    <div class="col-md-8 col-lg-6 py-5">
                        
                        <!-- Floating Container Card Wrapper -->
                        <div class="form-card-wrapper">
                            <h4 class="mb-4 text-dark font-weight-bold">Update Carousel</h4>
                            
                            <form action="update_carousel.php" method="POST" enctype="multipart/form-data">
                                <input type="text" name="carousel_id" id="carousel_id" class="form-control" hidden value="">

                                <!-- Image Preview Block -->
                                <div class="mb-4 d-flex flex-column align-items-center text-center">
                                    <label class="align-self-start">Current Carousel Image</label>
                                    <div class="preview-box mt-1">
                                        <img id="image" src="img/carousel-1.jpg" width="120" class="img-fluid rounded object-fit-cover">
                                    </div>
                                </div>

                                <!-- File Input Field -->
                                <div class="mb-4">
                                    <label for="carouselImage">Choose New Image</label>
                                    <input type="file" id="carouselImage" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                                </div>

                                <!-- Caption Text Input Field -->
                                <div class="mb-4">
                                    <label for="text1">Text Caption</label>
                                    <input type="text" name="text1" id="text1" class="form-control" value="Good Health Is The Root Of All Heppiness">
                                </div>

                                <!-- Action Buttons Layout -->
                                <div class="d-flex justify-content-end mt-2">
                                    <input type="submit" value="Submit Changes" class="btn btn-primary text-white px-4 py-2 font-weight-bold" style="border-radius: 6px;">
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

         <?php require('comm/footer.php')?>
        </div>

        <a href="#" class="btn btn-primary btn-lg text-white btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

  <?php require('comm/script.php')?>
</body>

</html>