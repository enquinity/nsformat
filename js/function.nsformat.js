function nsformat(text/*, args*/) {
    if (!window.NsGeneralFormatterInstance) {
        window.NsGeneralFormatterInstance = new NsGeneralFormatter();
    }
    var params = Array.prototype.slice.call(arguments);
    params.shift();
    return window.NsGeneralFormatterInstance.formatArr(text, params);
}
