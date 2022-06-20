<?php
/**
 * @var string $errorMessage
 * @var string $inlineCss (sanitized)
 */
?>

<?php if ($inlineCss): ?>
    <style>
        <?php echo $inlineCss ?>
    </style>
<?php endif ?>

<div class="lse-form-global-error">
    Laposta Signup Embed foutmelding:<br>
    <?php echo esc_html($errorMessage) ?>
</div>