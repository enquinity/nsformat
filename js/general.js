
function NsGeneralFormatter() {
    // EMPTY
}

NsGeneralFormatter.prototype = Object.create(NsBaseFormatter.prototype);
NsGeneralFormatter.prototype.constructor = NsBaseFormatter;

NsGeneralFormatter.prototype.formatValue = function(val, formatter, precision, formattervals) {
    switch (formatter) {
        case 'b':
            return parseInt(val, 10).toString(2);
            break;
        case 'c':
            return String.fromCharCode(parseInt(val, 10));
            break;
        case 'd':
        case 'i':
            return parseInt(val, 10);
            break;
        case 'e':
            return precision ? parseFloat(val).toExponential(precision) : parseFloat(val).toExponential();
            break;
        case 'f':
            return precision ? parseFloat(val).toFixed(precision) : parseFloat(val);
            break;
        case 'g':
            return precision ? parseFloat(val).toPrecision(precision) : parseFloat(val);
            break;
        case 'o':
            return val.toString(8);
            break;
        case 's':
            return val;
            break;
        case 'T':
        case 'type':
            if (typeof val === 'number') {
                return 'number'
            }
            else if (typeof val === 'string') {
                return 'string'
            }
            else {
                return Object.prototype.toString.call(val).slice(8, -1).toLowerCase()
            }
            break;
        case 'u':
            return parseInt(val, 10) >>> 0;
            break;
        case 'x':
            return parseInt(val, 10).toString(16);
            break;
        case 'X':
            return parseInt(val, 10).toString(16).toUpperCase();
            break;
    }
};
