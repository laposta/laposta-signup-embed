<?php

use Laposta\SignupEmbed\Plugin;

/**
 *
 * @var string $optionGroup
 * @var string $apiKey
 * @var array $lists
 * @var string $status
 * @var string $statusMessage
 * @var string $refreshCacheUrl
 */

use Laposta\SignupEmbed\Service\DataService;

?>

<div class="lse-settings wrap" data-reset-cache-url="<?php echo esc_url($refreshCacheUrl) ?>">

    <h1>Laposta Signup Embed Instellingen</h1>

    <form method="post" action="options.php" autocomplete="off">

        <?php @settings_fields($optionGroup); ?>
        <section>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="<?php echo Plugin::OPTION_API_KEY ?>">API key</label></th>
                    <td><input type="text" name="<?php echo Plugin::OPTION_API_KEY ?>" id="<?php echo Plugin::OPTION_API_KEY ?>" value="<?php echo esc_html($apiKey) ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Zijn niet alle lijsten zichtbaar?</th>
                    <td>
                        <a href="#" class="button button-primary js-reset-cache">Reset Cache</a>
                        <span style="display: none;" class="lsb-settings__reset-cache-result js-reset-result-success">De cache is geleegd</span>
                        <span style="display: none;"  class="lsb-settings__reset-cache-result js-reset-result-error">Er ging iets mis</span>
                    </td>
                </tr>
            </table>
        </section>

        <?php if ($status && $status !== DataService::STATUS_OK): ?>
            <section class="lse-settings__error">
                <h2 class="lse-settings__error-title">Foutmelding</h2>
                <p class="lse-settings__error-text">
                    Helaas is er iets misgegaan. Bekijk deze foutmelding: <br>
                    <?php echo esc_html($statusMessage) ?>
                </p>
            </section>
        <?php endif; ?>


        <!-- note: the option input fields must be there, otherwise they will be made empty when saving just the api key, therfore display: none instead of not outputting at all -->
        <div <?php if ($status !== DataService::STATUS_OK): ?>style="display: none"<?php endif ?>>
            <section class="lse-settings__lists">
                <h2 class="lse-settings__lists-title">Selecteer een lijst</h2>
                <p class="lse-settings__lists-text">
                    De onderstaande lijsten zijn gekoppeld aan de opgegeven API key.<br>
                    Per lijst kun je instellen waar je het ingebed aanmeldformulier wilt tonen.
                </p>
                <h4>Klik op een lijst om de opties van die lijst in te stellen.</h4>
                <?php foreach ($lists as $list): ?>
                    <a class="lse-settings__list js-list" href="#"
                       data-list-id="<?php echo esc_attr($list['list_id']) ?>"
                       data-list-name="<?php echo esc_attr($list['name']) ?>"
                    >
                        <?php echo esc_html($list['name']) ?>
                    </a>
                <?php endforeach ?>
                <input type="hidden" class="js-lists-settings" name="<?php echo Plugin::OPTION_LISTS_SETTINGS ?>"
                       value="<?php echo htmlspecialchars(json_encode(json_decode(get_option(Plugin::OPTION_LISTS_SETTINGS))), ENT_QUOTES) ?>">
                <div class="js-list-container" style="display: none">
                    <h2>Instellingen van de lijst: <span class="js-list-name"></span></h2>

                    <div class="lse-settings__show-options">
                        <h4 class="lse-settings__show-options-title">Waar wil je het ingebed aanmeldformulier tonen?</h4>
                        <?php $showOptions = [
                            DataService::SHOW_OPTION_NEVER => 'Nergens',
                            DataService::SHOW_OPTION_ALWAYS => 'Op elke pagina',
                            DataService::SHOW_OPTION_SHORTCODE => 'Specifieke pagina\'s',
                        ] ?>
                        <div class="lse-settings__show-option-items">
                            <?php foreach ($showOptions as $key => $val): ?>
                                <?php $key = esc_attr($key) ?>
                                <div class="lse-settings__show-option-item">
                                    <input class="lse-settings__show-option-input js-show-option-input js-select-target"
                                           data-target-selector=".js-show-option-info"
                                           type="radio"
                                           name="show_option"
                                           id="show_option_<?php echo esc_html($key) ?>"
                                           value="<?php echo esc_html($key) ?>"
                                    >
                                    <label for="show_option_<?php echo esc_html($key) ?>"><?php echo esc_html($val) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="lse-settings__show-option-info js-show-option-info"
                             data-select-target-value="<?php echo DataService::SHOW_OPTION_NEVER ?>"
                             style="display: none"
                        >
                            <h4>Het ingebed aanmeldformulier nergens tonen</h4>
                            <p>Als je kiest voor "nergens" tonen, hoef je niets te doen. <br>
                                Als je nog ergens de shortcode hebt geplaatst dan zal het ingebed aanmeldformulier daar alsnog getoond worden.</p>
                        </div>

                        <div class="lse-settings__show-option-info js-show-option-info"
                             data-select-target-value="<?php echo DataService::SHOW_OPTION_ALWAYS ?>"
                             style="display: none"
                        >
                            <h4>Het ingebed aanmeldformulier tonen op elke pagina</h4>
                            <p>Als je kiest voor tonen "op elke pagina", dan zal het externe javascript bestand op elke pagina ingeladen worden in de head. De instellingen van het ingebed aanmeldformulier op laposta.nl zullen daarbij gerespecteerd worden.</p>
                            <p>Let op: kies deze optie niet als je hebt gekozen voor de weergave 'Op de plek van de code'.</p>
                            <p>Wil je de instellingen van het ingebed aanmeldformulier aanpassen?
                                <a class="js-laposta-embed-link" href="" target="_blank">Klik dan hier.</a>
                            </p>
                        </div>

                        <div class="lse-settings__show-option-info js-show-option-info"
                             data-select-target-value="<?php echo DataService::SHOW_OPTION_SHORTCODE ?>"
                             style="display: none"
                        >
                            <h4>Het ingebed aanmeldformulier tonen op specifieke pagina's</h4>
                            <p>Als je het formulier op een specifieke pagina wilt tonen dan kan dit door deze shortcode te plaatsen op de plek naar keuze:</p>
                            <code class="laposta-code lse-settings__lists-shortcode-example">
                                [<?php echo Plugin::SHORTCODE_RENDER_FORM ?> list_id="<span class="js-list-id"></span>"]
                            </code>
                            <p>Het externe javascript bestand zal dan precies op de plek van de shortcode ingeladen worden. De instellingen van het ingebed aanmeldformulier op laposta.nl zullen daarbij gerespecteerd worden.</p>
                            <p>Wil je de instellingen van het ingebed aanmeldformulier aanpassen?
                                <a class="js-laposta-embed-link" href="" target="_blank">Klik dan hier.</a>
                            </p>
                        </div>
                    </div>
                </div>

            </section>

        </div>

        <?php @submit_button(); ?>

    </form>





</div>