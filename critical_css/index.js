const critical = require('critical')
const express = require('express')
const path = require('path')
const cors = require('cors')
const timeout = require('connect-timeout')
const helmet = require('helmet')
const fs = require('fs')
const e = require('connect-timeout')
const postcss = require('postcss')
const discard = require('postcss-discard')
const postcss_url = require('postcss-url')



const port = 3000
const app = express()

app.use(helmet())

// app.use(cors({
//     origin: '*'
// }))

const cors_options = {
    origin: 'https://api.criticalcss.ru/critical-css',
    optionsSuccessStatus: 200 // some legacy browsers (IE11, various SmartTVs) choke on 204
}

app.use(express.json());
app.use(express.urlencoded({ extended: true }))


// Functions
const check_token = function (req, res, next) {
    const jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiQVBJR0FURVdBWSIsImlhdCI6MTUxNjIzOTAyMn0.web-rfD4ID9kLLKbIIWkJnpSIkQ_aH2C3MYZ0ozD1jU'
    const req_token = req.body.apigateway_token

    if (req_token !== jwt) {
        res.send('Token not found!')
    } else {
        next()
    }
}

function halt_on_timedout(req, res, next) {
    if (!req.timedout) next()
}

function add_log_file(message = '') {
    try {
        fs.appendFileSync('logs.txt', message + ';' + "\r\n", 'utf8')
    } catch (err) {
        console.log('Error logging: ' + err)
    }
}

/**
 * Удаление CSS правил из критических стилей.
 * @param {string} orig_css - оригинальный CSS
 * @param {array} atrule - массив правил, которые необходимо удалить
 * @param {array} rule - массив CSS селекторов, которые необходимо удалить
 * @param {object} css_allowed - обьект разрешенных CSS селекторов и свойств
 * 
 * @return {string} css
 */
function remove_css_rule(orig_css = '', atrule = [], rule = [], css_allowed = {}) {
    if (!orig_css) return orig_css

    try {
        const output = postcss(
            [
                discard({
                    atrule: atrule,
                    rule: rule,
                    /**
                     * Обработка критических стилей.
                     * @param {object} node - узел node.
                     * @param {string} value
                     * @return {boolean} false (оставить node в крит. стилях), true (удалить node из крит стилей)
                     */
                    decl: (node, value) => {
                        const css_selector = node.parent.selector
                        const css_prop = node.prop
                        const is_contain_selector = css_selector in css_allowed

                        // Проверяем содержится ли селектор в переданных настройках
                        // Если да, оставляем
                        if (!is_contain_selector) {
                            return false
                        }

                        // Если свойство есть в переданных настройках его разрешаем
                        if (css_allowed[css_selector].includes(css_prop)) {
                            return false
                        } else {
                            return true
                        }
                    },
                }),
            ]
        )
            .process(orig_css)
            .css

        return output
    } catch (error) {
        return orig_css
    }
}

const generate_critical = function (req, res) {
    if (req.timedout) {
        res.send('Timeout')
        return
    }

    const url = req.body.site_url.trim()
    const site_width = req.body.site_width ? Number(req.body.site_width) : 1300
    const site_height = req.body.site_height ? Number(req.body.site_height) : 900
    const ignore_atrule = req.body.ignore_atrule && Array.isArray(req.body.ignore_atrule) ? req.body.ignore_atrule : []
    const ignore_decl = req.body.ignore_decl ? req.body.ignore_decl : ''
    const rebase_from = req.body.rebase_from ? req.body.rebase_from : ''
    const rebase_to = req.body.rebase_to ? req.body.rebase_to : ''
    const cms = req.body.cms

    let ignore_rule = req.body.ignore_rule && Array.isArray(req.body.ignore_rule) ? req.body.ignore_rule : []

    // add_log_file(JSON.stringify({
    //     ignore_rule: ignore_rule,
    //     ignore_atrule: ignore_atrule,
    //     ignore_decl: ignore_decl
    // }))

    if (ignore_rule) {
        ignore_rule = ignore_rule.map((value, index, array) => {
            if (typeof value === 'string') {
                var value_split = value.split('/')

                if (value_split[1] && value_split[2]) {
                    return new RegExp(value_split[1], value_split[2])
                }

                return new RegExp(value_split[1])
            }

            if (typeof value === 'object') {
                return value
            }
        })
    }

    // Разрешенные CSS свойства
    let css_allowed = {}

    if (ignore_decl) {
        const ignore_decl_arr = ignore_decl.split(';')
        const obj = new Object()

        ignore_decl_arr.forEach((elem) => {
            const item = elem.trim()
            item_array = item.split(':')

            if (item_array[0]) {
                var obj_name = item_array[0]
                var obj_prop = item_array[1].trim()

                var obj_prop_array = obj_prop.split(',')
                var obj_props_array = []

                obj_prop_array.forEach((obj_prop_value) => {
                    var obj_prop_item = obj_prop_value.trim()
                    obj_props_array.push(obj_prop_item)
                })

                obj[obj_name] = obj_props_array
            }
        })

        css_allowed = obj
    }

    try {
        critical.generate({
            base: './',
            src: url,
            width: site_width,
            height: site_height,
            extract: true,
            rebase: false,
        })
            .then((data) => {
                data.css = remove_css_rule(data.css, ignore_atrule, ignore_rule, css_allowed)

                const rebase_options = {
                    url: 'rebase',
                }

                async function rebase_css_wait(rebase_from, rebase_to) {
                    return await postcss()
                        .use(postcss_url(rebase_options))
                        .process(data.css, {
                            from: rebase_from,
                            to: rebase_to
                        })
                }

                if (rebase_from) {
                    const rebase_css_promise = rebase_css_wait(rebase_from, rebase_to)

                    return Promise.all([rebase_css_promise, data.html, data.uncritical])
                }

                return [data, data.html, data.uncritical]
            })
            .then((data_array) => {
                const response_data = {
                    'critical_css': data_array[0].css ? data_array[0].css : false,
                    'html': data_array[1] ? data_array[1] : false,
                    'uncritical_css': data_array[2] ? data_array[2] : false,
                }

                res.status(200).json(response_data)
            })
            .catch((err) => {
                const error = JSON.stringify(err)
                res.status(422).json(error)
            })
    } catch (err) {
        const error = JSON.stringify(err)
        res.status(422).json(error)
    }
}

// Routes
app.get('/', function (req, res) {
    res.send('Generator css')
})

// POST method route
app.post('/', [cors(cors_options), check_token, timeout('190s'), halt_on_timedout, generate_critical])

app.listen(port, () => {
    console.log(`Microseries "critical css" works listening on port ${port}`)
})


