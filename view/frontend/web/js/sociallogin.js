define(["prototype"], function () {
    var SocialLoginPopup = new Class.create();
    SocialLoginPopup.prototype = {
        initialize: function (w, h, l, t) {
            this.screenX = typeof window.screenX != 'undefined' ? window.screenX : window.screenLeft;
            this.screenY = typeof window.screenY != 'undefined' ? window.screenY : window.screenTop;
            this.outerWidth = typeof window.outerWidth != 'undefined' ? window.outerWidth : document.body.clientWidth;
            this.outerHeight = typeof window.outerHeight != 'undefined' ? window.outerHeight : (document.body.clientHeight - 22);
            this.width = w ? w : 500;
            this.height = h ? h : 270;
            this.left = l ? l : parseInt(this.screenX + ((this.outerWidth - this.width) / 2), 10);
            this.top = t ? t : parseInt(this.screenY + ((this.outerHeight - this.height) / 2.5), 10);
            this.features = (
                'width=' + this.width +
                ',height=' + this.height +
                ',left=' + this.left +
                ',top=' + this.top
            );
        },
        openPopup: function (url, title) {
            window.open(url, title, this.features);
        },
        closePopup: function () {
            window.close();
        }
    };
    return SocialLoginPopup;
});
