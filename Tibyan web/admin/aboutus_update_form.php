<?php include('comm/header.php');?>

<style>
    /* Clean container card for the form layout */
    .form-card-wrapper {
        background: #ffffff;
        border-radius: 12px;
        padding: 40px 35px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Refined form labels and typography */
    .form-card-wrapper label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 6px;
        display: block;
        text-align: left;
    }

    /* Inputs styled to match standard template accents */
    .form-card-wrapper .form-control {
        border-radius: 6px;
        padding: 10px 15px;
        border: 1px solid #ced4da;
        background-color: #ffffff;
    }

    .form-card-wrapper .form-control:focus {
        border-color: #009CFF;
        box-shadow: 0 0 0 0.2rem rgba(0, 156, 255, 0.25);
    }

    /* Side-by-side display adjustments for custom uploaded images */
    .preview-box {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 8px;
        background-color: #f8f9fa;
        width: 130px;
        height: 130px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .preview-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
        border-radius: 4px;
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
                    <div class="col-12 col-lg-10 py-5">
                        
                        <div class="form-card-wrapper">
                            <h4 class="mb-4 text-dark font-weight-bold text-start border-bottom pb-3">Update About Us Content</h4>
                            
                            <form action="update_about.php" method="POST" enctype="multipart/form-data">
                                <input type="text" name="about_us_id" id="about_us_id" class="form-control" hidden value="">

                                <div class="row g-4 mb-4">
                                    <div class="col-md-6 d-flex flex-column align-items-center border-end border-light">
                                        <label class="align-self-start">About Image 1</label>
                                        <div class="preview-box my-2">
                                            <img id="image1" src="img/about-1.jpg">
                                        </div>
                                        <input type="file" name="image1" class="form-control w-100" accept="image/*" onchange="previewImage(this)">
                                    </div>

                                    <div class="col-md-6 d-flex flex-column align-items-center">
                                        <label class="align-self-start">About Logo Image</label>
                                        <div class="preview-box my-2">
                                            <img id="image2" src="img/logo.png">
                                        </div>
                                        <input type="file" name="image2" class="form-control w-100" accept="image/*" onchange="previewImage(this)">
                                    </div>
                                </div>

                                <hr class="my-4" style="color: rgba(0,0,0,0.1);">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="text1">Text 1</label>
                                        <input type="text" name="text1" id="text1" class="form-control" value="text1">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="text2">Text 2</label>
                                        <input type="text" name="text2" id="text2" class="form-control" value="text2">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="text3">Text 3</label>
                                        <input type="text" name="text3" id="text3" class="form-control" value="text3">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="text4">Text 4</label>
                                        <input type="text" name="text4" id="text4" class="form-control" value="text4">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="text5">Text 5</label>
                                        <input type="text" name="text5" id="text5" class="form-control" value="text5">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="text6">Text 6</label>
                                        <input type="text" name="text6" id="text6" class="form-control" value="text6">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="text7">Text 7</label>
                                        <input type="text" name="text7" id="text7" class="form-control" value="text7">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="text8">Text 8</label>
                                        <input type="text" name="text8" id="text8" class="form-control" value="text 8">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                    <input type="submit" value="Save Changes" class="btn btn-primary text-white px-4 py-2 font-weight-bold" style="border-radius: 6px;">
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