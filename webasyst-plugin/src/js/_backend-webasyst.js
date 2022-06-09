var app = new Vue({
    delimiters: ['%%', '%%'],
    el: '.start',
    data: {
        plugin_url: document.querySelector('#plugin_url').value,
        loading: true,
        button_save_disabled: false,
        selected_tab: 0,
        // // Settings default
        settings: {
            // active_plugin: false,
            tabs: [new String('Desktop')],
            add_settings: [
                {
                    icon: false,
                    tabs: [new String('Desktop')],
                    selected_tab: 0,
                },
            ]
        }
    },

    filters: {
        bool: function (value) {
            if (!value) return ''
            return Boolean(value)
        },

        int: function (value) {
            if (!value) return ''
            return Number(value)
        },

        toJson: function (value) {
            if (!value) return ''
            return JSON.stringify(value)
        },
        
        fromJson: function (value) {
            if (!value) return ''
            return JSON.parse(value)
        }
    },

    methods: {
        faqOpen(e) {
            if (!e.target.className || !/faq__question/gi.test(e.target.className)) return

            const answer = e.target.parentNode.children[1]
            const question = e.target.parentNode.children[0]
            const icon = question.children[0]

            if (/open/gi.test(answer.className)) {
                answer.className = answer.className.replace(/\s(open)/, '')
                icon.className = icon.className.replace(/darr/gi, 'rarr')
            } else {
                answer.className += ' open'
                icon.className = icon.className.replace(/rarr/gi, 'darr')
            }
        },

        selectTab(e, add_settings_index, index_tab) {
            Vue.set(this.settings.add_settings[add_settings_index], 'selected_tab', index_tab)
        },

        addTabs(e, index) {
            var tabs = this.settings.add_settings[index].tabs
            var index_mobile = tabs.indexOf('Mobile')
            var is_mobile_in_array = index_mobile != -1

            if (is_mobile_in_array) {
                tabs.splice(index_mobile, 1)
                this.settings.add_settings[index].selected_tab = 0
            } else {
                tabs.push('Mobile')
            }
            
        },

        addSettingsBlock() {
            this.settings.add_settings.push({
                icon: false,
                tabs: [new String('Desktop')],
                selected_tab: 0,
            })
        },

        delSettingsBlock() {
            if (this.settings.add_settings.length > 1) {
                this.settings.add_settings.pop()
            }
            
        },
    
        pageReload() {    
            setTimeout(function () {
                window.location.reload();
            }, 500);
        },
    },
        
    mounted: function () {
        this.settings = Object.assign({}, this.settings, settings_backend)
        this.loading = false

        this.settings.add_settings.forEach(elem => {
            if (elem.tabs && typeof elem.tabs === 'string') {
                elem.tabs = JSON.parse(elem.tabs)
            }
        })
    }
})