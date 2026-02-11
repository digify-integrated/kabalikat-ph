export const setHereClassForMenu = (menuContainerSelector, menuSelector) => {
  const menuContainer = document.querySelector(menuContainerSelector);
  if (!menuContainer) return;

  const normalizeUrl = (url) => {
    try {
      const u = new URL(url, window.location.origin);
      return u.pathname.replace(/\/+$/, '') || '/';
    } catch {
      return null;
    }
  };

  const currentPath = normalizeUrl(window.location.href);

  const menuItems = menuContainer.querySelectorAll(menuSelector);

  menuItems.forEach((menuItem) => {
    const childLinks = menuItem.querySelectorAll(':scope .menu-sub .menu-link[href]');

    if (childLinks.length) {
      const activeChild = Array.from(childLinks).find((link) => {
        const linkPath = normalizeUrl(link.getAttribute('href'));
        return linkPath && linkPath === currentPath;
      });

      if (activeChild) {
        activeChild.classList.add('active');
        menuItem.classList.add('here', 'show');
      }

      return;
    }

    const anchor = menuItem.querySelector(':scope > a[href], :scope .menu-link[href]');
    if (!anchor) return;

    const anchorPath = normalizeUrl(anchor.getAttribute('href'));
    if (anchorPath && anchorPath === currentPath) {
      menuItem.classList.add('here');
    }
  });
};
