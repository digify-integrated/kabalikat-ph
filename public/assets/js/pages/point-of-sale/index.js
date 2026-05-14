import { getPageContext, getCsrfToken } from '../../form/form.js';

document.addEventListener('DOMContentLoaded', () => {
    const generateShopRegister = (url) => {
        try {        
            const csrf = getCsrfToken();
            const ctx = getPageContext();
        
            const params = new URLSearchParams();
            params.append('appId', ctx.appId ?? '');
            params.append('navigationMenuId', ctx.navigationMenuId ?? '');
            appendObject(params, otherData);
        
            const response = await fetch(url, {
              method: 'POST',
              body: params,
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                Accept: 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
              },
            });
        
            if (!response.ok) throw new Error(`Request failed with status: ${response.status}`);
        
            const data = await response.json();
        
            if (data?.success) {
              await onSuccess(data);
              return data;
            }
        
            if (data?.notExist) {
              if (typeof onNotExist === 'function') onNotExist(data);
              else {
                setNotification(data.message);
                window.location.replace(data.redirect_link);
              }
              return data;
            }
        
            if (typeof onFailureMessage === 'function') onFailureMessage(data);
            else showNotification(data?.message ?? 'Request failed.');
        
            return data;
          } catch (error) {
            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
            throw error;
          } finally {
            setHiddenBusy(hideNodes, false);
            if (disableWhileFetching) setFormBusy(targetForm, false);
          }
    }

    generateShopRegister('/shop-register/generate-register');
});