import './bootstrap';

import Alpine from 'alpinejs';
window.Alpine = Alpine;

window.dispatchForm = function (itemData) {
    return {
        items: itemData.map(i => ({ ...i, dispatchQty: 0 })),
        totalQty: 0,
        syncTotal() {
            this.totalQty = this.items.reduce((s, i) => s + (parseInt(i.dispatchQty) || 0), 0);
        },
    };
};

Alpine.start();
