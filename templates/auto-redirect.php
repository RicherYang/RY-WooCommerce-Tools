<?php defined('ABSPATH') or exit; ?>

<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AutoSubmitForm</title>
    <script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function(){
        setTimeout(function() {
            document.getElementById("ry-auto-redirect").submit();
        }, 150);
    });
    </script>
</head>

<body>
    <p style="margin-top:100px;text-align:center">
        <?php echo esc_html__('Transaction data processing… DO NOT refresh or close the webpage.', 'ry-woocommerce-tools'); ?>
    </p>
    <form method="<?php echo esc_attr($method); ?>" id="ry-auto-redirect" action="<?php echo esc_url($redirect_url); ?>">
        <?php foreach ($redirect_data as $key => $value) { ?>
        <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
        <?php } ?>
    </form>
</body>

</html>
