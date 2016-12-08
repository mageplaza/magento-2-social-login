define(["jquery", "prototype"], function (jQuery) {
    var SocialLoginForm = new Class.create();
    SocialLoginForm.prototype = {
        initialize: function (config) {
            /*General*/
            this.headerLink = $$(config.headerLink).first();
            this.sectionLink = config.sectionLink;
            this.popup = $$(config.popup).first();
            this.loadingClass = 'social-login-ajax-loading';
            this.errorMsgClass = 'error-msg';
            this.successMsgClass = 'success-msg';
            /*Login*/
            this.loginFormContainer = $$(config.loginFormContainer).first();
            this.loginFormContent = $$(config.loginFormContent).first();
            this.loginForm = jQuery(config.loginForm);
            this.loginBtn = $$(config.loginBtn).first();
            this.createBtn = $$(config.createBtn).first();
            this.forgotBtn = $$(config.forgotBtn).first();
            this.formLoginUrl = config.formLoginUrl;
            /*Create*/
            this.createFormContainer = $$(config.createFormContainer).first();
            this.createFormContent = $$(config.createFormContent).first();
            this.createForm = jQuery(config.createForm);
            this.createAccBtn = $$(config.createAccBtn).first();
            this.createBackBtn = $$(config.createBackBtn).first();
            this.createFormUrl = config.createFormUrl;
            /*Forgot*/
            this.forgotFormContainer = $$(config.forgotFormContainer).first();
            this.forgotFormContent = $$(config.forgotFormContent).first();
            this.forgotForm = jQuery(config.forgotForm);
            this.forgotSendBtn = $$(config.forgotSendBtn).first();
            this.forgotBackBtn = $$(config.forgotBackBtn).first();
            this.forgotFormUrl = config.forgotFormUrl;
            /*Captcha*/
            this.loginCaptchaImg = $$(config.loginCaptchaImg).first();
            this.createCaptchaImg = $$(config.createCaptchaImg).first();
            this.forgotCaptchaImg = $$(config.forgotCaptchaImg).first();
            this.init(config);
            this.initObservers();
        },
        init: function (config) {
            if(this.headerLink) {
                this.headerLink.select('a').each(function (link) {
                    if (link) {
                        if (
                            link.href.search('/customer/account/login/') != -1 ||
                            link.href.search('/customer/account/create/') != -1
                        // link.href.search('/customer/account/login/') != -1 ||
                        // link.href.search('/wishlist/') != -1 ||
                        // link.href.search('/customer/account/') != -1
                        ) {
                            link.addClassName('social-login');
                            if (link.href.search('/customer/account/create/') != -1) {
                                link.addClassName('create');
                            }
                            link.href = config.popup;
                        }
                    }
                    this.section = 'login';
                    link.setAttribute('data-effect', config.popupEffect);

                }.bind(this));
            }
        },
        initObservers: function () {
            document.observe('dom:loaded', function () {
                /*Links*/
                if(this.headerLink) {
                    this.headerLink.select('a').each(function (el) {
                        el.observe('click', function (evt) {
                            var link = evt.target;
                            if (link.hasClassName('create')) {
                                this.section = 'create';
                            }
                            else {
                                this.section = 'login';
                            }
                            if (this.section == 'login') {
                                this.showLoginFormContainer();
                                this.hideCreateFormContainer();
                                this.hideForgotFormContainer();
                            }
                            else if (this.section == 'create') {
                                this.hideLoginFormContainer();
                                this.showCreateFormContainer();
                                this.hideForgotFormContainer();
                            }

                        }.bind(this));
                    }.bind(this));
                }
                if($$(this.sectionLink).first()) {
                    $$(this.sectionLink).first().select('a').each(function (el) {
                        el.observe('click', function (evt) {
                            var link = evt.target;
                            if (link.hasClassName('create')) {
                                this.section = 'create';
                                this.hideLoginFormContainer();
                                this.showCreateFormContainer();
                                this.hideForgotFormContainer();
                            }
                            else {
                                this.section = 'login';
                                this.showLoginFormContainer();
                                this.hideCreateFormContainer();
                                this.hideForgotFormContainer();
                            }

                        }.bind(this));
                    }.bind(this));
                }
            }.bind(this));
            /*Login*/
            this.loginBtn.observe('click', function () {
                this._processLogin();
            }.bind(this));
            this.createBtn.observe('click', function () {
                this.section = 'create';
                this.hideLoginFormContainer();
                this.showCreateFormContainer();
            }.bind(this));
            this.forgotBtn.observe('click', function () {
                this.section = 'forgot';
                this.hideLoginFormContainer();
                this.showForgotFormContainer();
            }.bind(this));
            /*Create*/
            this.createAccBtn.observe('click', function () {
                this._processCreate();
            }.bind(this));
            this.createBackBtn.observe('click', function () {
                this.section = 'login';
                this.hideCreateFormContainer();
                this.showLoginFormContainer();
            }.bind(this));
            /*Forgot*/
            this.forgotSendBtn.observe('click', function () {
                this._processForgot();
            }.bind(this));
            this.forgotBackBtn.observe('click', function () {
                this.section = 'login';
                this.hideForgotFormContainer();
                this.showLoginFormContainer();
            }.bind(this));
            document.observe('keypress', this._processKeyPress.bind(this));
        },
        showForgotFormContainer: function () {
            this.forgotFormContainer.show();
        },
        hideForgotFormContainer: function () {
            this.forgotFormContainer.hide();
        },
        showCreateFormContainer: function () {
            this.createFormContainer.show();
        },
        hideCreateFormContainer: function () {
            this.createFormContainer.hide();
        },
        showLoginFormContainer: function () {
            this.loginFormContainer.show();
        },
        hideLoginFormContainer: function () {
            this.loginFormContainer.hide();
        },
        _processKeyPress: function (e) {
            var code = e.keyCode || e.which;
            if (code == 13) {
                if (this.section == 'login') {
                    this._processLogin();
                }
                else if (this.section == 'create') {
                    this._processCreate();
                }
                else if (this.section == 'forgot') {
                    this._processForgot();
                }
            }
        },
        _processLogin: function () {
            if (this.loginForm.valid()) {
                this.appendLoading(this.loginFormContent);
                this.removeMsg(this.loginFormContent, this.errorMsgClass);
                var parameters = this.loginForm.serialize(true);
                var me = this;
                jQuery.ajax({
                    url: this.formLoginUrl,
                    type: 'POST',
                    data: parameters,
                    success: function (data, textStatus, xhr) {
                        var result = xhr.responseText.evalJSON();
                        if (result.success) {
                            me.addMsg(me.loginFormContent, result.message, me.successMsgClass);
                            window.location.reload(true);
                        } else {
                            me.removeLoading(me.loginFormContent);
                            if (result.imgSrc) {
                                if (me.loginCaptchaImg) {
                                    me.addMsg(me.loginFormContent, result.message, me.errorMsgClass);
                                    me.loginCaptchaImg.src = result.imgSrc;
                                }
                                else {
                                    window.location.reload();
                                }
                            }
                            else {
                                me.addMsg(me.loginFormContent, result.message, me.errorMsgClass);
                            }
                        }
                    }

                });
            }
        },
        _processForgot: function () {
            if (this.forgotForm.valid()) {
                this.appendLoading(this.forgotFormContent);
                this.removeMsg(this.forgotFormContent, this.errorMsgClass);
                this.removeMsg(this.forgotFormContent, this.successMsgClass);
                var parameters = this.forgotForm.serialize(true);
                var me = this;
                jQuery.ajax({
                    url: this.forgotFormUrl,
                    type: 'POST',
                    data: parameters,
                    success: function (data, textStatus, xhr) {
                        me.removeLoading(me.forgotFormContent);
                        var result = xhr.responseText.evalJSON();
                        if (result.success) {
                            me.addMsg(me.forgotFormContent, result.message, me.successMsgClass);
                        } else {
                            me.addMsg(me.forgotFormContent, result.message, me.errorMsgClass);
                        }
                        if (result.imgSrc) {
                            if (me.forgotCaptchaImg) {
                                me.forgotCaptchaImg.src = result.imgSrc;
                            }
                        }
                    }

                });
            }
        },
        _processCreate: function () {
            if (this.createForm.valid()) {
                this.appendLoading(this.createFormContent);
                this.removeMsg(this.createFormContent, this.errorMsgClass);
                var parameters = this.createForm.serialize(true);
                var me = this;
                jQuery.ajax({
                    url: this.createFormUrl,
                    type: 'POST',
                    data: parameters,
                    success: function (data, textStatus, xhr) {
                        var result = xhr.responseText.evalJSON();
                        if (result.success) {
                            me.addMsg(me.createFormContent, result.message, me.successMsgClass);
                            location.reload(true);
                        } else {
                            me.removeLoading(me.createFormContent);
                            if (result.imgSrc) {
                                if (me.createCaptchaImg) {
                                    me.addMsg(me.createFormContent, result.message, me.errorMsgClass);
                                    me.createCaptchaImg.src = result.imgSrc;
                                }
                                else {
                                    window.location.reload();
                                }
                            }
                            else {
                                me.addMsg(me.createFormContent, result.message, me.errorMsgClass);
                            }
                        }
                    }

                });
            }
        },
        appendLoading: function (block) {
            var ajaxLoading = new Element('div');
            block.setStyle({
                'position': 'relative'
            })
            ajaxLoading.addClassName(this.loadingClass);
            block.insertBefore(ajaxLoading, block.down());
        },
        removeLoading: function (block) {
            var selector = "." + this.loadingClass;
            block.setStyle({
                'position': ''
            })
            block.select(selector).each(function (el) {
                el.remove();
            });
        },
        removeMsg: function (block, messageClass) {
            block.select('.' + messageClass).each(function (el) {
                el.remove();
            })
        },
        addMsg: function (block, message, messageClass) {
            if (typeof(message) === 'object' && message.length > 0) {
                message.each(function (msg) {
                    this._appendMessage(block, msg, messageClass);
                }.bind(this));
            }
            else if (typeof(message) === 'string') {
                this._appendMessage(block, message, messageClass);
            }
        },
        _appendMessage: function (block, message, messageClass) {
            var currentMessage = null;
            var messageSection = block.select("." + messageClass + " ol");
            if (messageSection.length === 0) {
                var messageElement = new Element('div');
                messageElement.addClassName(messageClass);
                messageElement.appendChild(new Element('ol'));
                block.insertBefore(messageElement, block.down());
                currentMessage = messageElement.down();
            } else {
                currentMessage = messageSection.first();
            }
            var newMessage = new Element('li');
            newMessage.update(message);
            currentMessage.appendChild(newMessage);
        },
        showLoading: function (block) {
            block.setStyle({
                'opacity': 0.4
            });
            this.ajaxLoading.show();
        },
        hideLoading: function (block) {
            block.setStyle({
                'opacity': 1
            });
            this.ajaxLoading.hide();
        }
    };

    return SocialLoginForm;
});
