importFastb.panel.Import = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'importfastb-import-panel';
    }
    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        url: importFastb.config.connector_url,
        config: config,
        layout: 'anchor',
    	hideMode: 'offsets',
		fileUpload: true,
		baseParams: {
			action: 'mgr/prices/import'
		},

		items: [/*{
            xtype: 'textarea',
            fieldLabel: _('importfastb_csv'), 
            name: 'csv',
            id: config.id + '-import-csv',
            labelSeparator: '',
            anchor: '100%',
            autoHeight: false,
            height: 250,
            allowBlank: true,
            blankText: _('importfastb_nocsv')
        }, */{
            xtype: 'fileuploadfield',
            fieldLabel: _('importfastb_csvfile'),
            name: 'csv-file',
            id: config.id + '-csv-file',
            //inputType: 'file',
            style: {display: 'none'},
			listeners: {
				afterrender: {fn: this._fileInputAfterRender, scope: this}
			}
        }, {
            xtype: 'button',
            text: _('importfastb_import_start'),
            fieldLabel: _('importfastb_import_start'),
            name: 'start-import',
            id: config.id + '-start-import',
            style: {margin: '15px 0 0 15px', float: 'right'},
            cls: 'primary-button',
			listeners: {
				click: {fn: this._startImport, scope: this}
			}
        }, {
            xtype: 'button',
            text: _('importfastb_import_file_select'),
            fieldLabel: _('importfastb_import_file_select'),
            name: 'csv-file-btn',
            id: config.id + '-csv-file-btn',
            style: {margin: '15px 15px 0 0', display: 'inline-block'},
			listeners: {
				click: {fn: this._selectCSV, scope: this}
			}
        }, {
			id: config.id + '-csv-file-filename-holder',
			anchor: '50%',
			style: {margin: '15px 15px 0 0', display: 'inline-block', padding: '12px 10px', 'vertical-align': 'top'},
		}, {
            xtype: 'modx-panel',
            id: config.id + '-log',
            anchor: '100%',
            autoHeight: true,
            cls: 'panel-desc',
            style: {display: 'none', 'max-height': '250px', overflow: 'auto'}
        }]
	});
	importFastb.panel.Import.superclass.constructor.call(this, config);
};
Ext.extend(importFastb.panel.Import, MODx.FormPanel, {
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
        Ext.getCmp('importfastb-import-panel-log').body.dom.innerHTML = "";
        document.getElementById('importfastb-import-panel-log').style.display = "none";
        /*document.getElementById(e.target.config.id + '-csv-file-btn').classList.add('x-item-disabled');
        e.target.setAttribute("disabled", "disabled");*/
    },
    
    _startImport: function() {
        Ext.getCmp(this.config.id).form.submit({
            url: importFastb.config.connector_url,
            success: function(form, response){
                //console.log(form);
                var panel = Ext.getCmp(form.config.id);
                panel._processImport(response.result);
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
    
    _processImport: function(response) {
        var lineSeparator = '<br />';
        var logcontainer = document.getElementById(this.config.id + '-log');
        var currentlog = Ext.getCmp(this.config.id + '-log').body.dom.innerHTML;
        var importlog = currentlog ? currentlog.split(lineSeparator) : [];
        if (logcontainer.style.display == "none") {
            logcontainer.style.display = "block";
        }
	    importlog = importlog.concat(response.object.log);
	    Ext.getCmp(this.config.id + '-log').update(importlog.join(lineSeparator));
        logcontainer.scrollTop = logcontainer.scrollHeight;
        if (!response.object.complete) {
            MODx.Ajax.request({
            	url: importFastb.config.connector_url
            	,params: {
            		action: 'mgr/prices/import',
            		parsed: response.object.parsed || 0,
            		step:   response.object.step || 0,
					filename: response.object.filename || ''
            	}
            	,listeners: {
            		success: {fn: function(response) {
            		    var indicator = document.getElementById('processing-xls');
						if (indicator) {
							indicator.setAttribute('class', '');
						}
            			this._processImport(response)
            		}, scope: this}
            	}
            });
        }
    }
    
});
Ext.reg('importfastb-import-panel', importFastb.panel.Import);