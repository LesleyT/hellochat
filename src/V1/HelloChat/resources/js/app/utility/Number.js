let exported = true;
export { exported as ExportNumber };

Number.prototype.pad = function(size, location) {
	if(this < 0) { return this; }
	if(location !== 'r'){ location = 'l'; }
	var s = String(this);
	while (s.length < (size || 2)) {
		s = (location === 'l') ? '0' + s : s + '0';
	}
	return s;
};