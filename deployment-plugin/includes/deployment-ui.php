<?php
if (!defined('ABSPATH')) {
    exit;
}

// Register the Deployment admin page in the left menu.
add_action('admin_menu', 's3_deployment_admin_menu');
function s3_deployment_admin_menu()
{
    add_menu_page(
        'Deployment', // Page title
        'Deployment', // Menu title
        'manage_options', // Capability required
        'deployment-plugin', // Menu slug
        's3_deployment_page_callback', // Callback function to render the page
        'dashicons-upload', // Icon
        65 // Position
    );
}

/**
 * Renders the Deployment admin page.
 */
function s3_deployment_page_callback()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions to access this page.'));
    }

    // Check for deployment actions and call processor functions
    if (isset($_POST['deploy_staging']) && check_admin_referer('deploy_staging_action', 'deploy_staging_nonce')) {
        process_staging_deployment();
    }

    if (isset($_POST['deploy_prod']) && check_admin_referer('deploy_prod_action', 'deploy_prod_nonce')) {
        if (empty($_POST['confirm_prod'])) {
            $msg = "Please confirm production deployment.";
            error_log($msg);
            echo "<div class='error'><p>" . esc_html($msg) . "</p></div>";
        } else {
            process_production_deployment();
        }
    }

    // If "Generate Page Content" button is submitted, call the function and print the JSON to the console.
    if (isset($_POST['generate_page_content']) && check_admin_referer('generate_page_content_action', 'generate_page_content_nonce')) {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/shared_data/staging/';
        $json_content = generate_page_content_json_file($target_dir);

        // Output inline JavaScript that prints the JSON content to the browser console.
        echo '<script>';
        echo 'console.log("Page Content JSON:", ' . $json_content . ');';
        echo '</script>';

        echo "<div class='updated'><p>Page content data printed to console. Open DevTools → Console.</p></div>";
    }

    // Retrieve deployment info for production (if exists)
    $upload_dir = wp_upload_dir();
    $base_upload_path = $upload_dir['basedir'];
    $prod_target_dir = $base_upload_path . '/shared_data/prod/';
    $prod_file = $prod_target_dir . 'data.json';
    $prod_deployment = file_exists($prod_file) ? file_get_contents($prod_file) : '';
    ?>
    <style>
        .deployment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .deploy-btn {
            font-size: 1.5em;
            padding: 20px 40px;
            margin-bottom: 10px;
            display: inline-block;
        }

        .deployment-info {
            font-size: 1.2em;
            margin-top: 10px;
        }

        .deploy-form {
            border: 2px solid #ccc;
            padding: 20px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .form-title {
            font-size: 1.4em;
            margin-bottom: 10px;
        }

        .confirm-container {
            margin-top: 15px;
        }

        .data-button {
            margin-top: 30px;
        }

        /* Add styling for data output tables if needed */
        .partner-data-table,
        .artist-data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .partner-data-table th,
        .partner-data-table td,
        .artist-data-table th,
        .artist-data-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .partner-data-table th,
        .artist-data-table th {
            background-color: #f2f2f2;
        }
    </style>
    <div class="wrap">
        <h1>Deployment</h1>
        <div class="deployment-grid">
            <div class="deploy-form">
                <div class="form-title">Deploy to Staging</div>
                <form method="post">
                    <?php wp_nonce_field('deploy_staging_action', 'deploy_staging_nonce'); ?>
                    <input type="hidden" name="deploy_staging" value="1">
                    <?php submit_button('Deploy to Staging', 'primary deploy-btn'); ?>
                </form>
            </div>
            <div class="deploy-form">
                <div class="form-title">Deploy to Production</div>
                <form method="post" id="prod-form">
                    <?php wp_nonce_field('deploy_prod_action', 'deploy_prod_nonce'); ?>
                    <input type="hidden" name="deploy_prod" value="1">
                    <div class="confirm-container">
                        <label>
                            <input type="checkbox" name="confirm_prod" required>
                            I understand that this is a production deployment and that it will affect the live website.
                        </label>
                    </div>
                    <?php submit_button('Deploy to Production', 'primary deploy-btn'); ?>
                </form>
                <?php if ($prod_deployment): ?>
                    <div class="deployment-info">
                        Latest production deployment: <?php echo esc_html($prod_deployment); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="deploy-form">
            <div class="form-title">Generate Page Content JSON</div>
            <form method="post" id="page-content-form">
                <?php wp_nonce_field('generate_page_content_action', 'generate_page_content_nonce'); ?>
                <input type="hidden" name="generate_page_content" value="1">
                <?php submit_button('Generate Page Content', 'secondary deploy-btn', 'generate_page_content_btn'); ?>
            </form>
        </div>
    </div>
    <script>
        // Optional: log a message when the form is submitted.
        const pageContentForm = document.getElementById('page-content-form');
        if (pageContentForm) {
            pageContentForm.addEventListener('submit', function () {
                console.log("Generating Page Content JSON...");
            });
        }
    </script>
    <?php
}
