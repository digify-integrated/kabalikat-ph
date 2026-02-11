const DEFAULT_TOASTR_OPTIONS = Object.freeze({
  closeButton: false,
  debug: false,
  newestOnTop: false,
  progressBar: true,
  preventDuplicates: false,
  onclick: null,
  hideDuration: 2000,
  timeOut: 3000,
  extendedTimeOut: 1000,
  showMethod: "fadeIn",
  hideMethod: "fadeOut",
});

const TOASTR_METHOD = Object.freeze({
  success: "success",
  info: "info",
  warning: "warning",
  error: "error",
});

export const showNotification = ({
  title = "",
  message = "",
  type = "info",
  duration = 500,
  position = "toastr-top-right",
} = {}) => {

  toastr.options = {
    ...DEFAULT_TOASTR_OPTIONS,
    positionClass: position,
    showDuration: duration,
  };

  const method = TOASTR_METHOD[type] ?? "info";
  toastr[method](message, title);
};