export const initializeDualListBoxIcon = () => {
    $('.moveall i').removeClass().addClass('ki-duotone ki-right');
    $('.removeall i').removeClass().addClass('ki-duotone ki-left');
    $('.move i').removeClass().addClass('ki-duotone ki-right');
    $('.remove i').removeClass().addClass('ki-duotone ki-left');

    $('.moveall, .removeall, .move, .remove')
        .removeClass('btn-default')
        .addClass('btn-primary');
};