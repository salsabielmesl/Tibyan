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
        white-space: nowrap;
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

                            <div class="table-card-wrapper">
                                <div class="table-responsive ">
                                    <table class="table table-hover table-bordered align-middle table-striped m-0" id="datatable">
                                        <thead>
                                            <tr>
                                                <th>image 1</th>
                                                <th>image 2</th>
                                                <th>text1</th>
                                                <th>text2</th>
                                                <th>text3</th>
                                                <th>text4</th>
                                                <th>text5</th>
                                                <th>text6</th>
                                                <th>text7</th>
                                                <th>text8</th>
                                                <th>actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                                <tr>
                                                    <td><img src="img/about-1.jpg" height="60px" width="60px" class="rounded object-fit-cover"></td>
                                                    <td><img src="img/logo.png" height="60px" width="60px" class="rounded object-fit-cover"></td>
                                                    <td><p class="m-0">Why You Should Trust Us? Get Know About Us!</p></td>
                                                    <td><p class="m-0">Tebyān is an intelligent healthcare platform designed to support early detection and risk assessment of diabetes using advanced artificial intelligence. Our goal is to make health insights more accessible, accurate, and easy to understand for everyone.</p></td>
                                                    <td><p class="m-0">By combining medical knowledge with modern machine learning techniques, Tebyan helps users make informed decisions about their health. We focus on simplicity, reliability, and delivering results that users can trust.</p></td>
                                                    <td><p class="m-0">Our system is built with a strong foundation in data science and healthcare principles, ensuring that every prediction is based on meaningful analysis—not guesswork.</p></td>
                                                    <td>We believe technology should empower people to take control of their health, and Tebyan is a step toward smarter, preventive care.</td>
                                                    <td>AI-Powered Health Predictions</td>
                                                    <td>Data-Driven Medical Insights</td>
                                                    <td>User-Friendly & Accessible Design</td>
                                                    <td>
                                                        <a href="aboutus_update_form.php" class="btn btn-success btn-action text-white">
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
        <a href="#" class="btn btn-primary btn-lg text-white btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <?php require('comm/script.php')?>
</body>

</html>