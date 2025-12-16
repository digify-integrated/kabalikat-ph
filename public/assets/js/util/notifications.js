export const showNotification = (
  message,
  type = 'info',
  duration = 3000,
  position = 'right',
  gravity = 'top'
) => {
  const validTypes = ['success', 'info', 'warning', 'error'];
  
  if (!validTypes.includes(type)) {
    console.warn(`Invalid toast type: "${type}". Defaulting to "info".`);
    type = 'info';
  }

  const backgroundColors = {
    success: '#32d484',
    info: '#00c9ff',
    warning: '#fdaf22',
    error: '#ff6757',
  };

  Toastify({
    text: message,
    duration: duration,
    gravity: gravity,
    position: position,
    newWindow: true,
    close: true,
    stopOnFocus: true,
    backgroundColor: backgroundColors[type],
  }).showToast();
};