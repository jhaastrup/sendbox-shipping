(function($) {
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
  $(document).ready(function() {
    /**
     * Check if we are specially on the sendbox shipping method settings page.
     */
    if (
      window.location.href.indexOf(
        "?page=wc-settings&tab=shipping&section=wooss"
      ) > 0
    ) {
      var wc_button_save = $("button.button-primary.woocommerce-save-button");
      wc_button_save.show();
      var wooss_basic_auth;
      var wooss_connect_btn = $("button.wooss-connect-sendbox");
      var wooss_errors_message_span = $("span.wooss_errors_pages");
      var wooss_loader = $("img#wooss-loader");
      /**
       * AJAX function to check if the API key is valid.
       */

      wooss_connect_btn.on("click", function(e) {
        e.preventDefault();
        wooss_basic_auth = $("input[name='wooss_basic_auth'").val();
        function reloadPage() {
          window.location.reload();
        }
        wooss_loader.show();
        var data = {
          wooss_basic_auth: wooss_basic_auth
        };
        $.post(
          wooss_ajax_object.wooss_ajax_url,
          {
            action: "connect_to_sendbox",
            data: data,
            security: wooss_ajax_object.wooss_ajax_security
          },
          function(response) {
            wooss_loader.hide();
            if (1 == response) {
              $(
                '<div id="message" class="updated inline"><p><strong>Your API key is valid, this page will be refreshed in 10s.</strong></p></div>'
              ).insertAfter("br.clear");
              setTimeout(reloadPage, 10000);
            } else if (0 == response) {
              wooss_errors_message_span
                .append(
                  "Invalid API key login to sendbox and get the correct key"
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
      wooss_button_save.on("submit click", function(e) {
        e.preventDefault();
        var wooss_state_name = $("select.wooss_state_dropdown").val();
        var wooss_country = $("select.wooss_country_select").val();
        var wooss_city = $("input[name='wooss_city']").val();
        var wooss_street = $("input[name='wooss_street']").val();
        var wooss_pickup_type = $("select.wooss_pickup_type").val();
        var wooss_rates_type = $("select.wooss_rates_type").val();
        wooss_basic_auth = $("input[name='wooss_basic_auth'").val();
        var wooss_enabled = $('input[name="woocommerce_wooss_enabled"]').val();
        var wooss_extra_fees = $("input#wooss_extra_fees").val();

        var data = {
          wooss_state_name: wooss_state_name,
          wooss_country: wooss_country,
          wooss_city: wooss_city,
          wooss_street: wooss_street,
          wooss_basic_auth: wooss_basic_auth,
          wooss_enabled: wooss_enabled,
          wooss_pickup_type: wooss_pickup_type,
          wooss_rates_type: wooss_rates_type,
          wooss_extra_fees: wooss_extra_fees
        };

        $.post(
          wooss_ajax_object.wooss_ajax_url,
          {
            action: "save_fields_by_ajax",
            data: data,
            security: wooss_ajax_object.wooss_ajax_security
          },
          function(response) {
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
