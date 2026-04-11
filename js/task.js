var NagTasks = {
    knl: {},

    /**
     * Attaches a KeyNavList drop down to one of the time fields.
     *
     * @param string|Element field  A time field (id).
     *
     * @return KeyNavList  The drop down list object.
     */
    attachTimeDropDown: function(field, time_format)
    {
        var list = [], d = new Date(), time, opts;

        d.setHours(0);
        d.setMinutes(0);
        do {
            time = d.toString(time_format);
            list.push({ l: time, v: time });
            d.add(30).minutes();
        } while (d.getHours() !== 0 || d.getMinutes() !== 0);

        field = document.getElementById(field);
        opts = {
            list: list,
            onChoose: function(value) {
                if (value) {
                    field.value = value;
                }
            }.bind(this)
        };

        this.knl[field.id] = new KeyNavList(field, opts);

        return this.knl[field.id];
    },

    /**
     * Keypress handler for time fields.
     */
    timeSelectKeyHandler: function(e)
    {
        switch(e.key) {
        case 'ArrowUp':
        case 'ArrowDown':
        case 'ArrowRight':
        case 'ArrowLeft':
            return;
        default:
            var dt = document.getElementById('due_time');
            if (dt.value !== this.knl[dt.id].getCurrentEntry()) {
                this.knl[dt.id].markSelected(null);
            }
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    var dropDown = NagTasks.attachTimeDropDown('due_time', Nag.conf.time_format);
    var dt = document.getElementById('due_time');
    dt.addEventListener('click', function() { dropDown.show(); }); // eslint-disable-line horde/no-prototype-methods -- KeyNavList.show()
    dt.addEventListener('keyup', NagTasks.timeSelectKeyHandler.bind(NagTasks));
});
