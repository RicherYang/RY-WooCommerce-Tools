<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AutoSubmitForm</title>
</head>

<body>
    <p style="margin-top:100px;text-align:center">
        <?php echo esc_html__('Transaction data processing… DO NOT refresh or close the webpage.', 'ry-woocommerce-tools'); ?>
    </p>
    <form method="post" id="RY-auto-post-redirect" action="<?php echo esc_url($redirect_url); ?>">
        <?php foreach ($redirect_data as $key => $value) { ?>
        <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
        <?php } ?>
    </form>
    <script type="text/javascript">
        document.getElementById("RY-auto-post-redirect").submit();
    </script>
</body>

</html>
