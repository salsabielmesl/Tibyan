<?php include('comm/header.php'); ?>

<!-- Custom styling to match the screenshot layout with your blue theme -->
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
        display: inline-flex;
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

            <div class="container-fluid pt-4">
                <div class="row g-4 min-vh-100 rounded justify-content-center mx-0">
                    <div class="col-12">
                        <div class="bg-light rounded h-100 p-4">
                            <div class="container">

                            <!-- Styled Container Card matching the design theme layout -->
                            <div class="table-card-wrapper mt-3">
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle table-striped m-0" id="datatable">
                                        <thead>
                                            <tr>
                                                <th>image 1</th>
                                                <th>text1</th>
                                                <th>text2</th>
                                                <th>text3</th>
                                                <th>text4</th>
                                                <th>text5</th>
                                                <th>text6</th>
                                                <th>text7</th>
                                                <th style="width: 100px;">actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                                <tr>
                                                    <td><img src="img/logo.png" height="60px" width="60px" class="rounded object-fit-cover"></td>
                                                    <td>Why You Should Trust Us? Get Know About Us!</td>
                                                    <td>Our Mission</td>
                                                    <td>At Tebyan, our mission is to make healthcare smarter and more accessible through the power of artificial intelligence. We aim to support early detection of diabetes and help individuals better understand their health through simple, data-driven insights.</td>
                                                    <td>Our Vision</td>
                                                    <td>We envision a future where technology plays a key role in preventive healthcare—where everyone has access to intelligent tools that empower them to make informed decisions and live healthier lives.</td>
                                                    <td>Our Approach</td>
                                                    <td>We combine medical knowledge with modern machine learning techniques to create a system that is accurate, reliable, and easy to use. Tebyan is designed with the user in mind—turning complex health data into clear and actionable information.</td>
                                                    <td>
                                                        <a href="about_update_form.php" class="btn btn-success btn-action text-white">
                                                            <i class="fas fa-edit"></i>
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

        </div>
        <!-- Content End -->

        <!-- Back to Top -->
        <a href="#" class="btn btn-primary btn-lg text-white btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
  <?php require('comm/script.php')?>
</body>

</html>