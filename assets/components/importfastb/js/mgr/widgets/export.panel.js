importFastb.panel.Export = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'importfastb-export-panel';
    }
    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        url: importFastb.config.connector_url,
        config: config,
        layout: 'anchor',
        hideMode: 'offsets',
		fileUpload: true,
		baseParams: {
			action: 'mgr/prices/export'
		},

		items: [{
            xtype: 'panel',
            style: {"margin-bottom":"15px"},
            items: [{
                xtype: 'xcheckbox',
                name: 'category',
                id: 'export-category-checkbox',
                boxLabel: 'Category',
                checked: true
            }, {
                xtype: 'xcheckbox',
                name: 'pagetitle',
                id: 'export-pagetitle-checkbox',
                boxLabel: 'Title',
                checked: true
            }, {
                xtype: 'xcheckbox',
                name: 'longtitle',
                id: 'export-longtitle-checkbox',
                boxLabel: 'H1',
                checked: true
            }, {
                xtype: 'xcheckbox',
                name: 'description',
                id: 'export-description-checkbox',
                boxLabel: 'Description',
                checked: true
            }, {
                xtype: 'xcheckbox',
                name: 'alias',
                id: 'export-alias-checkbox',
                boxLabel: 'URL',
                checked: true
            }, {
                xtype: 'xcheckbox',
                name: 'content',
                id: 'export-content-checkbox',
                boxLabel: 'Content',
                checked: true
            }, {
                xtype: 'xcheckbox',
                name: 'tvs',
                id: 'export-tvs-checkbox',
                boxLabel: 'TVs',
                checked: false
            }]
        }, {
            xtype: 'button',
            text: _('importfastb_export_start'),
            fieldLabel: _('importfastb_export_start'),
            name: 'start-export',
            id: config.id + '-start-export',
            cls: 'primary-button',
			listeners: {
				click: {fn: this._startexport, scope: this}
			}
        }, {
            xtype: 'modx-panel',
            id: config.id + '-export-log',
            anchor: '100%',
            autoHeight: true,
            cls: 'panel-desc',
            style: {display: 'none', 'max-height': '250px', overflow: 'auto'}
        }]
	});
	importFastb.panel.Export.superclass.constructor.call(this, config);
};
Ext.extend(importFastb.panel.Export, MODx.FormPanel, {
    _selectCSV: function() {
        document.getElementById(this.config.id + '-csv-file-file').click();
    },
    
    _fileInputAfterRender: function() {
        document.getElementById(this.config.id + '-csv-file-file').addEventListener('change', this._showFileName, false);
        document.getElementById(this.config.id + '-csv-file-file').style.display = "none";
        document.getElementById(this.config.id + '-csv-file-file').nextSibling.style.display = "none";
    },
    
    _showFileName: function(e) {
        document.getElementById(e.target.id + 'name-holder').innerHTML = this.files[0].name;
        Ext.getCmp('importfastb-export-panel-export-log').body.dom.innerHTML = "";
        document.getElementById('importfastb-export-panel-export-log').style.display = "none";
        /*document.getElementById(e.target.config.id + '-csv-file-btn').classList.add('x-item-disabled');
        e.target.setAttribute("disabled", "disabled");*/
    },
    
    _startexport: function() {
        Ext.getCmp(this.config.id).form.submit({
            url: importFastb.config.connector_url,
            success: function(form, response){
                //console.log(form);
                var panel = Ext.getCmp(form.config.id);
                panel._processexport(response.result);
            },
            failure: function(form, response){
                for (i=0;i<response.result.errors.length;i++) {
                    //console.log(response.result.errors[i]);
                    if (response.result.errors[i].id == 'csv-file-btn') {
                        document.getElementById(form.config.id + '-csv-file-filename-holder').innerHTML =
                            '<span class="red">' + response.result.errors[i].msg + '</span>';
                        document.getElementById(form.config.id + '-csv-file-file-btn').classList.remove('x-item-disabled');
                        document.getElementById(form.config.id + '-csv-file-file').removeAttribute("disabled");
                    }
                }
                //Ext.MessageBox.alert('Ошибка авторизации. ',response.result.message);
            }
        });
    },
    
    _processexport: function(response) {
        var lineSeparator = '<br />';
        var logcontainer = document.getElementById(this.config.id + '-export-log');
        var currentlog = Ext.getCmp(this.config.id + '-export-log').body.dom.innerHTML;
        var exportlog = currentlog ? currentlog.split(lineSeparator) : [];
        if (logcontainer.style.display == "none") {
            logcontainer.style.display = "block";
        }
        exportlog = exportlog.concat(response.object.log);
	    Ext.getCmp(this.config.id + '-export-log').update(exportlog.join(lineSeparator));
        logcontainer.scrollTop = logcontainer.scrollHeight;
        if (!response.object.complete) {
            MODx.Ajax.request({
            	url: importFastb.config.connector_url
            	,params: {
            		action: 'mgr/prices/export',
            		parsed: true,
            		step:   response.object.step || 0,
					filename: response.object.filename || '',
					exported: response.object.exported || '',
					category: Ext.getCmp('export-category-checkbox').checked,
					pagetitle: Ext.getCmp('export-pagetitle-checkbox').checked,
					longtitle: Ext.getCmp('export-longtitle-checkbox').checked,
					description: Ext.getCmp('export-description-checkbox').checked,
					alias: Ext.getCmp('export-alias-checkbox').checked,
					content: Ext.getCmp('export-content-checkbox').checked,
					tvs: Ext.getCmp('export-tvs-checkbox').checked,
            	}
            	,listeners: {
            		success: {fn: function(response) {
						var indicator = document.getElementById('creating-xls');
						if (indicator) {
							indicator.setAttribute('class', '');
						}
            			this._processexport(response);
            		}, scope: this}
            	}
            });
        } else {
			MODx.Ajax.request({
				url: MODx.config.connector_url
				,params: {
					action: 'browser/file/download'
					,file: response.object.filepath
				}
				,listeners: {
					'success':{fn:function(r) {
						location.href = MODx.config.connector_url+'?action=browser/file/download&download=1&file='+response.object.filepath+'&HTTP_MODAUTH='+MODx.siteId+'&wctx='+MODx.ctx;
					},scope:this}
				}
			});
		}
    }
    
});
Ext.reg('importfastb-export-panel', importFastb.panel.Export);