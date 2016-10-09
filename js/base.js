/*
NSFormat JS Version 1.0.0
 */

function NsBaseFormatter() {
}

NsBaseFormatter.prototype.tokenize = function(text) {
    var tokens = [];
    var cursor = 0;
    while (true) {
        var i = text.indexOf('{', cursor);
        if (i == -1) {
            if (cursor < text.length) tokens.push([1, text.substr(cursor)]);
            break;
        }
        if (i > cursor) {
            tokens.push([1, text.substr(cursor, i - cursor)]);
        }
        var i2 = text.indexOf('}', i);
        if (i2 == -1) {
            throw "Unmatched brace";
        }
        tokens.push([2, text.substr(i + 1, i2 - i - 1)]);
        cursor = i2 + 1;
    }
    return tokens;
};

NsBaseFormatter.prototype.formatValue = function(val, formatter, precision, formattervals) {
    return val;
};

NsBaseFormatter.prototype.parseIndex = function(index, result) {
    if (!index || index == '') {
        result.value = '';
        return;
    }
    result.value = null;
    result.valIndex = null;
    if ('@' == index[0]) {
        // todo: dopracowac
        result.valIndex = index.substr(1);
        return;
    }
    if ('=' == index[0]) {
        result.value = index.substr(1);
        return;
    }
    if (index[0] >= '0' && index[0] <= '9') {
        result.valIndex = index;
        return;
    }
    retult.value = index;
};

NsBaseFormatter.prototype.format = function(text) {
    var params = Array.prototype.slice.call(arguments);
    params.shift();
    return this.formatArr(text, params);
};

NsBaseFormatter.prototype.formatArr = function(text, vals) {
    var padStr = function(pad, str, padLeft) {
        if (typeof str === 'undefined')
            return pad;
        if (padLeft) {
            return (pad + str).slice(-pad.length);
        } else {
            return (str + pad).substring(0, pad.length);
        }
    }

    var tokens = this.tokenize(text);
    var fmt = '';
    for (var i = 0; i < tokens.length; i++) {
        var token = tokens[i];
        switch (token[0]) {
            case 1:
                fmt += token[1];
                break;

            case 2:
                var tt = token[1].split(':');

                var emptyVal = null;
                var format = null;
                var width = null;
                var maxWidth = null;
                var formatter = null;
                var formattervals = null;
                var paddingChar = ' ';
                var paddingType = 1; // 1: pad right, -1: pad left, 2: pad center
                var precision = null;

                var ci = 1;
                if (tt.length > ci && tt[ci][0] == '~') {
                    emptyVal = tt[ci].substr(1);
                    ci++;
                }
                if (tt.length > ci) {
                    format = tt[ci];
                    fmtMatch = format.match(/([-+*].\d+|[-+*]?\d+)?(-\d+)?(\.\d+)?(\w+)\s*(.*)/);
                    if (!fmtMatch) {
                        throw "Bad format " + format;
                    }
                    if (fmtMatch[1]) {
                        // szerokosc todo
                        var p = fmtMatch[1];
                        var pi = 0;
                        var possiblePaddingChar = true;
                        switch (p[pi++]) {
                            case '-': paddingType = -1; break;
                            case '+': paddingType = 1; break;
                            case '*': paddingType = 2; break;
                            case '0': paddingChar = 0; possiblePaddingChar = false; break;
                            default:
                                pi--;
                                possiblePaddingChar = false;
                        }
                        if (possiblePaddingChar && (p[pi] <= '0' || p[pi] > '9')) {
                            paddingChar = p[pi];
                            pi++;
                        }
                        width = pi > 0 ? p.substr(pi) : p;
                    }
                    if (fmtMatch[2]) {
                        maxWidth = fmtMatch[2].substr(1);
                    }
                    if (fmtMatch[3]) {
                        precision = fmtMatch[3].substr(1);
                    }
                    formatter = fmtMatch[4];
                    if (fmtMatch[5]) {
                        formattervals = fmtMatch[4].split(';').map(function(e) {e = e + ''; return e.trim();});
                    }

                    ci++;
                }

                var indexInfo = {};
                this.parseIndex(tt[0], indexInfo);

                var val = indexInfo.value !== null ? indexInfo.value : vals[indexInfo.valIndex];

                var fval = this.formatValue(val, formatter, precision, formattervals);

                if (width) {
                    var padCnt = width - fval.length + 1;
                    if (padCnt > 0) {
                        switch (paddingType) {
                            case -1:
                                fval += Array(padCnt).join(paddingChar);
                                break;
                            case 1:
                                fval = Array(padCnt).join(paddingChar) + fval;
                                break;
                            case 2:
                                var padCntLeft = parseInt(padCnt / 2);
                                fval = Array(padCntLeft).join(paddingChar) + fval + Array(padCnt - padCntLeft).join(paddingChar);
                                break;
                        }
                    }
                }
                if (maxWidth && fval.length > maxWidth) {
                    fval = fval.substr(0, maxWidth);
                }

                fmt += fval;
                break;
        }
    }
    return fmt;
};