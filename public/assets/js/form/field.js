import { handleSystemError } from '../util/system-errors.js';
import { getCsrfToken } from '../form/form.js';

export const initializeDualListBoxIcon = () => {
    $('.moveall i').removeClass().addClass('ki-duotone ki-right');
    $('.removeall i').removeClass().addClass('ki-duotone ki-left');
    $('.move i').removeClass().addClass('ki-duotone ki-right');
    $('.remove i').removeClass().addClass('ki-duotone ki-left');

    $('.moveall, .removeall, .move, .remove')
        .removeClass('btn-default')
        .addClass('btn-primary');
};

export const generateDropdownOptions = async ({
  url,
  dropdownSelector,
  data = {},
  validateOnChange = false
}) => {
  try {
    const csrf = getCsrfToken();
    const formData = new URLSearchParams();
    for (const key in data) {
      if (Object.prototype.hasOwnProperty.call(data, key)) {
        formData.append(key, data[key]);
      }
    }

    const response = await fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            Accept: 'application/json',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch dropdown data. HTTP status: ${response.status}`);
    }

    const result = await response.json();
    const $dropdown = $(dropdownSelector);

    if ($dropdown.hasClass('select2-hidden-accessible')) {
      $dropdown.select2('destroy');
    }

    $dropdown.empty();

    const $modalParent      = $dropdown.closest('.modal');
    const $offcanvasParent  = $dropdown.closest('.offcanvas');
    const $menuParent       = $dropdown.closest('[data-kt-menu="true"]');

    let dropdownParent = $(document.body);
    if ($modalParent.length) {
      dropdownParent = $modalParent;
    } else if ($offcanvasParent.length) {
      dropdownParent = $offcanvasParent;
    } else if ($menuParent.length) {
      dropdownParent = $menuParent;
    }

    $dropdown.select2({
      data: result,
      dropdownParent,
      width: '100%',
      escapeMarkup: function (markup) {
        return markup;
      },
      templateResult: function (data) {
        return data.text;
      },
      templateSelection: function (data) {
        return data.text;
      }
    })
    .on('select2:open', function () {
      focusSelect2Search();
    })
    .on('select2:unselect select2:clear', function () {
      const $this = $(this);
      setTimeout(() => $this.select2('close'), 0);
    });

    $(document)
      .off('mousedown.select2-remove-close')
      .on('mousedown.select2-remove-close', '.select2-selection__choice__remove', function (e) {
        const $container  = $(this).closest('.select2');
        const $select     = $container.prevAll('select').first();

        if ($select.length && $select.data('select2')) {
          e.stopPropagation();
          setTimeout(() => $select.select2('close'), 0);
        }
      });

    if (validateOnChange) {
      $dropdown.on('change', function () {
        $(this).valid();
      });
    }

  } catch (error) {
    handleSystemError(error, 'fetch_failed', `Dropdown generation failed: ${error.message}`);
  }
};

function focusSelect2Search() {
  setTimeout(() => {
    const searchField = document.querySelector('.select2-container--open .select2-search__field');
    if (searchField) {
      searchField.focus();
    }
  }, 100);
}

export const generateDualListBox = async ({ trigger, url, selectSelector, data = {} }) => {
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest(trigger);
    if (!btn) return;     
  
    try {
      const formData = new URLSearchParams();
      for (const key in data) {
        if (Object.prototype.hasOwnProperty.call(data, key)) {
          formData.append(key, data[key]);
        }
      }
        
      const csrf = getCsrfToken();
      const response = await fetch(url, {
        method: 'POST',
        body: formData,      
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          Accept: 'application/json',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
      });

      if (!response.ok) {
        throw new Error(`Failed to fetch dual list box data. HTTP status: ${response.status}`);
      }

      const result = await response.json();

      const select = document.getElementById(selectSelector);
      if (!select) {
        return;
      }

      select.options.length = 0;
      result.forEach(opt => {
        const option = new Option(opt.text, opt.id);
        select.appendChild(option);
      });

      if ($(`#${selectSelector}`).length) {
        $(`#${selectSelector}`).bootstrapDualListbox({
          nonSelectedListLabel: 'Non-selected',
          selectedListLabel: 'Selected',
          preserveSelectionOnMove: 'moved',
          moveOnSelect: false,
          helperSelectNamePostfix: false
        });

        $(`#${selectSelector}`).bootstrapDualListbox('refresh', true);
        initializeDualListBoxIcon();
      }
    } catch (error) {
      handleSystemError(error, 'fetch_failed', `Dual list box generation failed: ${error.message}`);
    }
  });
};

export const initializeTinyMCE = (tiny_mce_id, disabled = 0) => {
    let options = {
        selector: tiny_mce_id,
        height : "350",
        toolbar: [
            'styleselect fontselect fontsizeselect',
            'undo redo | cut copy paste | bold italic | link image | alignleft aligncenter alignright alignjustify',
            'bullist numlist | outdent indent | blockquote subscript superscript | advlist | autolink | lists charmap | preview |  code | table tabledelete | tableprops tablerowprops tablecellprops | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol'
        ],
        plugins: 'advlist autolink link image lists charmap preview table code',
        license_key: 'gpl'
    };

    if (KTThemeMode.getMode() === "dark") {
        options["skin"] = "oxide-dark";
        options["content_css"] = "dark";
    }

    tinymce.init(options);

    if(disabled){
        tinymce.activeEditor.mode.set('readonly');
    }
}

export const initializeDatePicker = ({
  selector,
  enableTime = false,
  dateFormat = "M d, Y"
}) => {
  $(selector).flatpickr({
    enableTime,
    dateFormat
  });
};

export const initializeDateRangePicker = (
  selector,
  {
    startDate = null, // Removed default moment()
    endDate = null,   // Removed default moment()
    ranges = {
      Today: [moment(), moment()],
      Yesterday: [
        moment().subtract(1, "days"),
        moment().subtract(1, "days")
      ],
      "Last 7 Days": [moment().subtract(6, "days"), moment()],
      "Last 30 Days": [moment().subtract(29, "days"), moment()],
      "This Month": [
        moment().startOf("month"),
        moment().endOf("month")
      ],
      "Last Month": [
        moment().subtract(1, "month").startOf("month"),
        moment().subtract(1, "month").endOf("month")
      ]
    },
    callback = null
  } = {}
) => {
  const cb = (start, end) => {
    if (typeof callback === "function") {
      callback(start, end);
    }
  };

  const options = {
    autoUpdateInput: false, // Prevents the plugin from auto-filling the input
    ranges,
    locale: {
      cancelLabel: "Clear" // Optional: changes the 'Cancel' button to say 'Clear'
    }
  };

  // Only pass startDate and endDate to options if they exist
  if (startDate) options.startDate = startDate;
  if (endDate) options.endDate = endDate;

  const $element = $(selector);

  // Initialize the picker
  $element.daterangepicker(options, cb);

  // Update the input field only when the user clicks "Apply"
  $element.on("apply.daterangepicker", function (ev, picker) {
    $(this).val(
      picker.startDate.format("MM/DD/YYYY") +
        " - " +
        picker.endDate.format("MM/DD/YYYY")
    );
  });

  // Clear the input field when the user clicks "Clear" (Cancel)
  $element.on("cancel.daterangepicker", function (ev, picker) {
    $(this).val("");
  });

  // Trigger the initial callback and set initial value ONLY if dates were explicitly provided
  if (startDate && endDate) {
    cb(startDate, endDate);
    $element.val(
      startDate.format("MM/DD/YYYY") + " - " + endDate.format("MM/DD/YYYY")
    );
  } else {
    // Ensure the input starts completely blank
    $element.val("");
  }
};