import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { es } from 'vuetify/locale'

export default createVuetify({
  components,
  directives,
  locale: { locale: 'es', messages: { es } },
  theme: {
    defaultTheme: 'incarail',
    themes: {
      incarail: {
        dark: false,
        colors: {
          primary: '#0b3d2e',
          secondary: '#4f7a68',
          accent: '#c8102e',
          error: '#c8102e',
          background: '#f5f7f6',
        },
      },
    },
  },
})
