jQuery(document).ready(function() {
  const isStackCommerceConnectPage = document.querySelector('.stackcommerce-wp-form');

  if(isStackCommerceConnectPage) {
    let sc_connect = new StackCommerce_WP();

    sc_connect.updateForm();

    jQuery('#stackcommerce_wp_categories').select2({
      tags: [],
      tokenSeparators: [','],
      minimumInputLength: 3,
      containerCssClass: 'stackcommerce-wp-select2-container',
      dropdownCssClass: 'stackcommerce-wp-select2-dropdown'
    });

    jQuery('#stackcommerce_wp_tags').select2({
      tags: [],
      tokenSeparators: [','],
      minimumInputLength: 3,
      containerCssClass: 'stackcommerce-wp-select2-container',
      dropdownCssClass: 'stackcommerce-wp-select2-dropdown'
    });

    let $submit_button = document.querySelector('#stackcommerce-wp-form-submit');

    $submit_button.addEventListener('click', sc_connect.notify.bind(sc_connect));

    jQuery('form.stackcommerce-wp-form').keypress(sc_connect.performKeycheck.bind(sc_connect));
    jQuery('input[name="stackcommerce_wp_content_integration[]"]').change(sc_connect.updateForm.bind(sc_connect));
  }
});

class StackCommerce_WP {
  constructor() {
    this.$endpoint      = this.getValue('#stackcommerce_wp_cms_api_endpoint');
    this.$form          = document.querySelector('#stackcommerce-wp-form');
    this.$submit_button = document.querySelector('#stackcommerce-wp-form-submit');
  }

  notify() {
    let $account_id = this.getValue('#stackcommerce_wp_account_id'),
        $secret_key = this.getValue('#stackcommerce_wp_secret');

    this.setConnectionStatus('connecting');

    jQuery.ajax({
      method: 'PUT',
      url: this.$endpoint + '/api/wordpress/?id=' + $account_id + '&secret=' + $secret_key,
      processData: false,
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify(this.generatePayload())
    })
    .done(() => {
      this.setConnectionStatus('connected');
      this.$form.submit();
    })
    .fail(() => {
      this.setConnectionStatus('disconnected');
    });
  }

  generatePayload() {
    let $account_id     = this.getValue('#stackcommerce_wp_account_id'),
        $wordpress_url  = this.getValue('#stackcommerce_wp_endpoint'),
        $author_id      = this.getValue('#stackcommerce_wp_author'),
        $post_status    = this.getValue('#stackcommerce_wp_post_status'),
        $categories     = this.getSelect2Values('#stackcommerce_wp_categories', []),
        $tags           = this.getSelect2Values('#stackcommerce_wp_tags', []),
        $featured_image = this.getValue('#stackcommerce_wp_featured_image'),
        $plugin_version = this.getValue('#stackcommerce_wp_plugin_version');

    return {
      data: {
        type: 'partner_wordpress_settings',
        id: $account_id,
        attributes: {
          installed: true,
          wordpress_url: $wordpress_url,
          author_id: $author_id,
          post_status: $post_status,
          categories: $categories,
          tags: $tags,
          featured_image: $featured_image,
          plugin_version: $plugin_version
        }
      }
    };
  }

  setConnectionStatus(status) {
    this.setValue('#stackcommerce_wp_connection_status', status);
    this.updateForm();
  }

  updateForm() {
    let $form                = document.querySelector('#stackcommerce-wp-form'),
        $submit_button       = document.querySelector('#stackcommerce-wp-form-submit'),
        $connection_status   = this.getValue('#stackcommerce_wp_connection_status'),
        $content_integration = this.getValue('input[name="stackcommerce_wp_content_integration[]"]:checked');

    $form.setAttribute('data-stackcommerce-wp-status', $connection_status);
    $form.setAttribute('data-stackcommerce-wp-content-integration', ($content_integration == 'true' ? true : false));

    if($connection_status === 'connecting') {
      $submit_button.setAttribute('disabled', 'disabled');
    } else {
      $submit_button.removeAttribute('disabled');
    }
  }

  performKeycheck(e) {
    if(e.which == 13) {
      this.notify();
      return false;
    }
  }

  getValue($selector, default_value = '') {
    let value = document.querySelector($selector).value;

    if(value) {
      return value;
    } else {
      return default_value;
    }
  }

  getSelect2Values($selector, default_value = []) {
    let value = jQuery($selector).select2('val');

    if(value) {
      return value;
    } else {
      return default_value;
    }
  }

  setValue($selector, value = '') {
    document.querySelector($selector).value = value;
  }
}
