(()=>{"use strict";var e={n:o=>{var t=o&&o.__esModule?()=>o.default:()=>o;return e.d(t,{a:t}),t},d:(o,t)=>{for(var r in t)e.o(t,r)&&!e.o(o,r)&&Object.defineProperty(o,r,{enumerable:!0,get:t[r]})},o:(e,o)=>Object.prototype.hasOwnProperty.call(e,o)};const o=window.jQuery;var t=e.n(o);let r;t()((function(){t()(document.body).on("updated_checkout",(function(e,o){if(void 0!==o&&(r=void 0,t()(".woocommerce-checkout .ry-cvs-hide").show(),t()(".woocommerce-checkout .ry-ecpay-cvs-hide").show(),t()(".woocommerce-checkout .ry-newebpay-cvs-hide").show(),t()(".woocommerce-checkout .ry-smilepay-cvs-hide").show(),void 0!==o.fragments.ry_shipping_info&&(o.fragments.ry_shipping_info.ecpay_home,!0===o.fragments.ry_shipping_info.ecpay_cvs&&(r=o.fragments.ry_shipping_info.postData,t()(".woocommerce-checkout .ry-cvs-hide").hide(),t()(".woocommerce-checkout .ry-ecpay-cvs-hide").hide(),t()(".ry-cvs-store-info").hide(),""!=t()("input#RY_CVSStoreID").val()&&(t()(".ry-cvs-store-info").show(),t()(".ry-cvs-store-info > span").hide(),""!=t()("input#RY_CVSStoreName").val()&&t()(".ry-cvs-store-info .store-name").text(t()("input#RY_CVSStoreName").val()).parent().show(),""!=t()("input#RY_CVSAddress").val()&&t()(".ry-cvs-store-info .store-address").text(t()("input#RY_CVSAddress").val()).parent().show(),""!=t()("input#RY_CVSTelephone").val()&&t()(".ry-cvs-store-info .store-telephone").text(t()("input#RY_CVSTelephone").val()).parent().show())),!0===o.fragments.ry_shipping_info.newebpay_cvs&&(t()(".woocommerce-checkout .ry-cvs-hide").hide(),t()(".woocommerce-checkout .ry-newebpay-cvs-hide").hide()),!0===o.fragments.ry_shipping_info.smilepay_cvs&&(t()(".woocommerce-checkout .ry-cvs-hide").hide(),t()(".woocommerce-checkout .ry-smilepay-cvs-hide").hide()))),null!==window.sessionStorage.getItem("RyTempCheckout")){let e=JSON.parse(window.sessionStorage.getItem("RyTempCheckout"));for(const o in e){let t=jQuery('[name="'+e[o].name+'"]');switch(t.prop("tagName")){case"INPUT":if("checkbox"==t.attr("type")){!1===t.prop("checked")&&t.trigger("click");break}if("radio"==t.attr("type")){t=jQuery('[name="'+e[o].name+'"][value="'+e[o].value+'"]'),!1===t.prop("checked")&&t.trigger("click");break}case"TEXTAREA":case"SELECT":const r=t.val();t.val(e[o].value),r!=e[o].value&&t.trigger("change")}}window.sessionStorage.removeItem("RyTempCheckout")}})),t()(".woocommerce-checkout").on("click",".ry-choose-cvs",(function(){let e=t()("form.checkout").serializeArray();e=e.filter((function(e){return"_"!=e.name.substring(0,1)&&"RY_"!=e.name.substring(0,3)&&-1===["terms"].indexOf(e.name)})),window.sessionStorage.setItem("RyTempCheckout",JSON.stringify(e));let o='<form id="RyECPayChooseCvs" action="'+t()(this).data("ry-url")+'" method="post">';for(const e in r)o+='<input type="hidden" name="'+e+'" value="'+r[e]+'">';window.innerWidth<1024&&(o+='<input type="hidden" name="Device" value="1">'),o+="</form>",document.body.innerHTML+=o,document.getElementById("RyECPayChooseCvs").submit()}))}))})();