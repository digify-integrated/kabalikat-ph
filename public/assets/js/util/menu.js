export const setHereClassForMenu = (menuContainerSelector, menuSelector) => {
  const menuContainer = document.querySelector(menuContainerSelector);
  if (!menuContainer) return;

  const normalizeUrl = (url) => {
    try {
      // Support relative URLs by anchoring to current origin
      const u = new URL(url, window.location.origin);
      // Compare only path (ignores query/hash), remove trailing slash
      return u.pathname.replace(/\/+$/, '') || '/';
    } catch {
      return null;
    }
  };

  const currentPath = normalizeUrl(window.location.href);

  // Only first-level items inside the container, depending on your markup
  const menuItems = menuContainer.querySelectorAll(menuSelector);

  menuItems.forEach((menuItem) => {
    // Prefer direct children: scope to avoid accidentally matching deeper trees
    const childLinks = menuItem.querySelectorAll(':scope .menu-sub .menu-link[href]');

    if (childLinks.length) {
      // Find the first matching child (lets us stop early)
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

    // No children: mark this menu item active if its own anchor matches
    const anchor = menuItem.querySelector(':scope > a[href], :scope .menu-link[href]');
    if (!anchor) return;

    const anchorPath = normalizeUrl(anchor.getAttribute('href'));
    if (anchorPath && anchorPath === currentPath) {
      menuItem.classList.add('here');
    }
  });
};
