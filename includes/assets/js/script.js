(function ($) {
  "use strict";

  /**
   * All of the code for your public-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */
  $(document).ready(function () {
    /**
     * Check if we are specially on the sendbox shipping method settings page.
     */
    if (
      window.location.href.indexOf(
        "?page=wc-settings&tab=shipping&section=wooss"
      ) > 0
    ) {
      //by The Blacker
      $(document).on("click dbclick", "#woocommerce_wooss_enabled", function (
        e
      ) {
        $("div.wooss-shipping-settings").hide();
        if ($(this).prop("checked")) {
          $("div.wooss-shipping-settings").show();
        }
      });

      var wc_button_save = $("button.button-primary.woocommerce-save-button");
      wc_button_save.show();
      //var wooss_basic_auth;
      var sendbox_auth_token;
      var sendbox_refresh_token;
      var sendbox_app_id;
      var sendbox_client_secret; 
      var wooss_connect_btn = $("button.wooss-connect-sendbox");
      var wooss_errors_message_span = $("span.wooss_errors_pages");
      var wooss_loader = $("img#wooss-loader");
      /**
       * AJAX function to check if the API key is valid.
       */

      wooss_connect_btn.on("click", function (e) {
        e.preventDefault(); 
        var formDetails = $('form').serializeJSON();
      console.log(formDetails.wooss);
        function reloadPage() {
          window.location.reload();
        }
        wooss_loader.show();
        var data = formDetails.wooss
        console.log(data)

        $.post(
          wooss_ajax_object.wooss_ajax_url,
          {
            action: "connect_to_sendbox",
            data: data,
            security: wooss_ajax_object.wooss_ajax_security,
          },
          function (response) {
            wooss_loader.hide();
            if (1 == response) {
              $(
                '<div id="message" class="updated inline"><p><strong>Your access token is valid, this page will be refreshed in 10s.</strong></p></div>'
              ).insertAfter("br.clear");
              setTimeout(reloadPage, 10000);
            } else if (0 == response) {
              wooss_errors_message_span
                .append(
                  "Invalid access and refresh token login to sendbox and get the correct tokens"
                )
                .show();
            }
          }
        );
        wooss_errors_message_span.empty().hide();
      });

      /**
       * AJAX function to save settings data.
       */
      var wooss_button_save = $("button.button-primary.wooss_save_button");
      wooss_button_save.on("submit click", function (e) {
        e.preventDefault();
        var wooss_state_name = $("select.wooss_state_name").val();
        var wooss_country = $("select.wooss_country_select").val();
        var wooss_city = $("input[name='wooss_city']").val();
        var wooss_street = $("input[name='wooss_street']").val();
        var wooss_pickup_type = $("select.wooss_pickup_type").val();
        var wooss_rates_type = $("select.wooss_rates_type").val();
        //wooss_basic_auth = $("input[name='wooss_basic_auth']").val();
        sendbox_auth_token = $("input[name='sendbox_auth_token']").val();
        sendbox_refresh_token = $("input[name='sendbox_refresh_token']").val();
        sendbox_app_id = $("input[name='sendbox_app_id']").val();
        sendbox_client_secret = $("input[name='sendbox_client_secret']").val();
        var wooss_enabled = $('input[name="woocommerce_wooss_enabled"]').val();
        var wooss_extra_fees = $("input#wooss_extra_fees").val();

        var data = {
          wooss_state_name: wooss_state_name,
          wooss_country: wooss_country,
          wooss_city: wooss_city,
          wooss_street: wooss_street,
          //wooss_basic_auth: wooss_basic_auth,
          sendbox_auth_token: sendbox_auth_token,
          sendbox_refresh_token: sendbox_refresh_token,
          sendbox_app_id: sendbox_app_id,
          sendbox_client_secret: sendbox_client_secret,
          wooss_enabled: wooss_enabled,
          wooss_pickup_type: wooss_pickup_type,
          wooss_rates_type: wooss_rates_type,
          wooss_extra_fees: wooss_extra_fees,
        };

        $.post(
          wooss_ajax_object.wooss_ajax_url,
          {
            action: "save_fields_by_ajax",
            data: data,
            security: wooss_ajax_object.wooss_ajax_security,
          },
          function (response) {
            if (1 == response) {
              $(
                '<div id="message" class="updated inline"><p><strong>Your settings have been synced.</strong></p></div>'
              ).insertAfter("br.clear");
            } else {
              alert("Sendbox Error !!!");
            }
          }
        );
      });
    }
  });
})(jQuery);
