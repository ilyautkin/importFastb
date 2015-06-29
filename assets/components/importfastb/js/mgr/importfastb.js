var importFastb = function (config) {
	config = config || {};
	importFastb.superclass.constructor.call(this, config);
};
Ext.extend(importFastb, Ext.Component, {
	page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}
});
Ext.reg('importfastb', importFastb);

importFastb = new importFastb();