<?php include('comm/header.php'); ?>

<style>
    /* Card wrapper mimic from screenshot */
    .table-card-wrapper {
        background: #ffffff;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Table header styling */
    #datatable {
        border-collapse: separate !important;
        border-spacing: 0;
    }

    #datatable thead tr {
        background-color: #009CFF !important;
    }

    #datatable thead th {
        color: #000000 !important;
        font-weight: 700;
        text-transform: capitalize;
        padding: 14px 16px !important;
        border-bottom: none !important;
        border-top: 1px solid rgba(0, 0, 0, 0.1) !important;
    }

    /* Apply borders smoothly to table elements */
    #datatable th, #datatable td {
        border-left: 1px solid rgba(0, 0, 0, 0.08) !important;
    }
    #datatable th:last-child, #datatable td:last-child {
        border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
    }
    #datatable tbody tr:last-child td {
        border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
    }

    /* Table row alignments and alternating colors */
    #datatable tbody tr {
        background-color: #ffffff !important;
        transition: background-color 0.2s ease;
    }
    #datatable.table-striped tbody tr:nth-of-type(odd) {
        background-color: #f4f9ff !important; /* Very soft light blue tint for alternating rows */
    }
    #datatable tbody td {
        padding: 14px 16px !important;
        color: #333333;
    }

    /* Action button overrides inside table */
    .btn-action {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
        display: inline-inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 2px;
    }

    /* Customizing DataTables control accents to match your blue */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #009CFF !important;
        border-color: #009CFF !important;
        color: #ffffff !important;
        border-radius: 6px;
    }
    
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 6px 12px;
    }
</style>

<div class="modal fade" id="addCarouselModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4">
            
            <div class="modal-header text-white rounded-top-4 bg-primary">
                <h5 class="modal-title text-white"> Add Carousel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form action="insert_services.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="image" name="image" placeholder="Enter an image" required autocomplete="off">
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="text" placeholder="Text" required>
                                <label>Text</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 gap-2">
                        <button type="submit" class="btn btn-primary text-white px-4">Submit </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">

    <?php require('comm/sidebar.php')?>

        <div class="content">
          <?php require('comm/navbar.php')?>

            <div class="container-fluid pt-4 px-4">
                <div class="row g-4 min-vh-100 rounded justify-content-center mx-0">
                    <div class="col-12">
                        <div class="bg-light rounded h-100 p-4">
                            
                            <div class="container mb-4">
                                <div class="row justify-content-end">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-primary text-white px-4 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addCarouselModal">
                                            <i class="fa fa-plus me-2"></i>Add Carousel
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="table-card-wrapper">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle table-striped m-0" id="datatable">
                                        <thead>
                                            <tr>
                                                <th>image</th>
                                                <th>text</th>
                                                <th style="width: 150px;">actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
    <tr>
        <td>
            <img src="img/carousel-1.jpg" height="60px" width="60px" class="rounded object-fit-cover">
        </td>
        <td>Good Health Is The Root Of All Heppiness</td>
        <td>
            <a href="carousel_update_form.php" class="btn btn-success btn-action text-white">
                <i class="fas fa-edit"></i>
            </a>
            <a href="carousel_delete.php" class="btn btn-danger btn-action text-white">
                <i class="fa fa-trash"></i>
            </a>
        </td>
    </tr>
</tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <?php include('comm/footer.php') ?>
        </div>
    </div>

    <a href="#" class="btn btn-primary btn-lg text-white btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

  <?php require('comm/script.php')?>
</body>
</html>