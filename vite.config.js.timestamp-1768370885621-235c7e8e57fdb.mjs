// vite.config.js
import VueI18nPlugin from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/@intlify/unplugin-vue-i18n/lib/vite.mjs";
import vue from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import vueJsx from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/@vitejs/plugin-vue-jsx/dist/index.mjs";
import laravel from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/laravel-vite-plugin/dist/index.js";
import { fileURLToPath } from "node:url";
import AutoImport from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/unplugin-auto-import/dist/vite.js";
import Components from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/unplugin-vue-components/dist/vite.js";
import {
  VueRouterAutoImports,
  getPascalCaseRouteName
} from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/unplugin-vue-router/dist/index.mjs";
import VueRouter from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/unplugin-vue-router/dist/vite.mjs";
import { defineConfig } from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/vite/dist/node/index.js";
import MetaLayouts from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/vite-plugin-vue-meta-layouts/dist/index.mjs";
import vuetify from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/vite-plugin-vuetify/dist/index.mjs";
import svgLoader from "file:///C:/xampp/htdocs/ZAL-ERP/node_modules/vite-svg-loader/index.js";
var __vite_injected_original_import_meta_url = "file:///C:/xampp/htdocs/ZAL-ERP/vite.config.js";
var vite_config_default = defineConfig({
  plugins: [
    // Docs: https://github.com/posva/unplugin-vue-router
    // ℹ️ This plugin should be placed before vue plugin
    VueRouter({
      getRouteName: (routeNode) => {
        return getPascalCaseRouteName(routeNode).replace(/([a-z\d])([A-Z])/g, "$1-$2").toLowerCase();
      },
      beforeWriteFiles: (root) => {
        root.insert(
          "/apps/email/:filter",
          "/resources/js/pages/apps/email/index.vue"
        );
        root.insert(
          "/apps/email/:label",
          "/resources/js/pages/apps/email/index.vue"
        );
      },
      routesFolder: "resources/js/pages"
    }),
    vue({
      template: {
        compilerOptions: {
          isCustomElement: (tag) => tag === "swiper-container" || tag === "swiper-slide"
        },
        transformAssetUrls: {
          base: null,
          includeAbsolute: false
        }
      }
    }),
    laravel({
      input: ["resources/js/main.js"],
      refresh: true
    }),
    vueJsx(),
    // Docs: https://github.com/vuetifyjs/vuetify-loader/tree/master/packages/vite-plugin
    vuetify({
      styles: {
        configFile: "resources/styles/variables/_vuetify.scss"
      }
    }),
    // Docs: https://github.com/dishait/vite-plugin-vue-meta-layouts?tab=readme-ov-file
    MetaLayouts({
      target: "./resources/js/layouts",
      defaultLayout: "default"
    }),
    // Docs: https://github.com/antfu/unplugin-vue-components#unplugin-vue-components
    Components({
      dirs: [
        "resources/js/@core/components",
        "resources/js/views/demos",
        "resources/js/components"
      ],
      dts: true,
      resolvers: [
        (componentName) => {
          if (componentName === "VueApexCharts")
            return {
              name: "default",
              from: "vue3-apexcharts",
              as: "VueApexCharts"
            };
        }
      ]
    }),
    // Docs: https://github.com/antfu/unplugin-auto-import#unplugin-auto-import
    AutoImport({
      imports: [
        "vue",
        VueRouterAutoImports,
        "@vueuse/core",
        "@vueuse/math",
        "vue-i18n",
        "pinia"
      ],
      dirs: [
        "./resources/js/@core/utils",
        "./resources/js/@core/composable/",
        "./resources/js/composables/",
        "./resources/js/utils/",
        "./resources/js/plugins/*/composables/*"
      ],
      vueTemplate: true,
      // ℹ️ Disabled to avoid confusion & accidental usage
      ignore: ["useCookies", "useStorage"],
      eslintrc: {
        enabled: true,
        filepath: "./.eslintrc-auto-import.json"
      }
    }),
    // Docs: https://github.com/intlify/bundle-tools/tree/main/packages/unplugin-vue-i18n#intlifyunplugin-vue-i18n
    VueI18nPlugin({
      runtimeOnly: true,
      compositionOnly: true,
      include: [
        fileURLToPath(
          new URL("./resources/js/plugins/i18n/locales/**", __vite_injected_original_import_meta_url)
        )
      ]
    }),
    svgLoader()
  ],
  define: { "process.env": {} },
  resolve: {
    alias: {
      "@core-scss": fileURLToPath(
        new URL("./resources/styles/@core", __vite_injected_original_import_meta_url)
      ),
      "@": fileURLToPath(new URL("./resources/js", __vite_injected_original_import_meta_url)),
      "@themeConfig": fileURLToPath(
        new URL("./themeConfig.js", __vite_injected_original_import_meta_url)
      ),
      "@core": fileURLToPath(new URL("./resources/js/@core", __vite_injected_original_import_meta_url)),
      "@layouts": fileURLToPath(
        new URL("./resources/js/@layouts", __vite_injected_original_import_meta_url)
      ),
      "@images": fileURLToPath(new URL("./resources/images/", __vite_injected_original_import_meta_url)),
      "@styles": fileURLToPath(new URL("./resources/styles/", __vite_injected_original_import_meta_url)),
      "@configured-variables": fileURLToPath(
        new URL("./resources/styles/variables/_template.scss", __vite_injected_original_import_meta_url)
      ),
      "@db": fileURLToPath(
        new URL("./resources/js/plugins/fake-api/handlers/", __vite_injected_original_import_meta_url)
      ),
      "@api-utils": fileURLToPath(
        new URL("./resources/js/plugins/fake-api/utils/", __vite_injected_original_import_meta_url)
      )
    }
  },
  build: {
    chunkSizeWarningLimit: 5e3
  },
  optimizeDeps: {
    exclude: ["vuetify"],
    entries: ["./resources/js/**/*.vue"]
  },
  server: {
    watch: {
      usePolling: false
    }
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJDOlxcXFx4YW1wcFxcXFxodGRvY3NcXFxcWkFMLUVSUFwiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9maWxlbmFtZSA9IFwiQzpcXFxceGFtcHBcXFxcaHRkb2NzXFxcXFpBTC1FUlBcXFxcdml0ZS5jb25maWcuanNcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfaW1wb3J0X21ldGFfdXJsID0gXCJmaWxlOi8vL0M6L3hhbXBwL2h0ZG9jcy9aQUwtRVJQL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IFZ1ZUkxOG5QbHVnaW4gZnJvbSBcIkBpbnRsaWZ5L3VucGx1Z2luLXZ1ZS1pMThuL3ZpdGVcIjtcclxuaW1wb3J0IHZ1ZSBmcm9tIFwiQHZpdGVqcy9wbHVnaW4tdnVlXCI7XHJcbmltcG9ydCB2dWVKc3ggZnJvbSBcIkB2aXRlanMvcGx1Z2luLXZ1ZS1qc3hcIjtcclxuaW1wb3J0IGxhcmF2ZWwgZnJvbSBcImxhcmF2ZWwtdml0ZS1wbHVnaW5cIjtcclxuaW1wb3J0IHsgZmlsZVVSTFRvUGF0aCB9IGZyb20gXCJub2RlOnVybFwiO1xyXG5pbXBvcnQgQXV0b0ltcG9ydCBmcm9tIFwidW5wbHVnaW4tYXV0by1pbXBvcnQvdml0ZVwiO1xyXG5pbXBvcnQgQ29tcG9uZW50cyBmcm9tIFwidW5wbHVnaW4tdnVlLWNvbXBvbmVudHMvdml0ZVwiO1xyXG5pbXBvcnQge1xyXG4gIFZ1ZVJvdXRlckF1dG9JbXBvcnRzLFxyXG4gIGdldFBhc2NhbENhc2VSb3V0ZU5hbWUsXHJcbn0gZnJvbSBcInVucGx1Z2luLXZ1ZS1yb3V0ZXJcIjtcclxuaW1wb3J0IFZ1ZVJvdXRlciBmcm9tIFwidW5wbHVnaW4tdnVlLXJvdXRlci92aXRlXCI7XHJcbmltcG9ydCB7IGRlZmluZUNvbmZpZyB9IGZyb20gXCJ2aXRlXCI7XHJcbmltcG9ydCBNZXRhTGF5b3V0cyBmcm9tIFwidml0ZS1wbHVnaW4tdnVlLW1ldGEtbGF5b3V0c1wiO1xyXG5pbXBvcnQgdnVldGlmeSBmcm9tIFwidml0ZS1wbHVnaW4tdnVldGlmeVwiO1xyXG5pbXBvcnQgc3ZnTG9hZGVyIGZyb20gXCJ2aXRlLXN2Zy1sb2FkZXJcIjtcclxuXHJcbi8vIGh0dHBzOi8vdml0ZWpzLmRldi9jb25maWcvXHJcbmV4cG9ydCBkZWZhdWx0IGRlZmluZUNvbmZpZyh7XHJcbiAgcGx1Z2luczogW1xyXG4gICAgLy8gRG9jczogaHR0cHM6Ly9naXRodWIuY29tL3Bvc3ZhL3VucGx1Z2luLXZ1ZS1yb3V0ZXJcclxuICAgIC8vIFx1MjEzOVx1RkUwRiBUaGlzIHBsdWdpbiBzaG91bGQgYmUgcGxhY2VkIGJlZm9yZSB2dWUgcGx1Z2luXHJcbiAgICBWdWVSb3V0ZXIoe1xyXG4gICAgICBnZXRSb3V0ZU5hbWU6IChyb3V0ZU5vZGUpID0+IHtcclxuICAgICAgICAvLyBDb252ZXJ0IHBhc2NhbCBjYXNlIHRvIGtlYmFiIGNhc2VcclxuICAgICAgICByZXR1cm4gZ2V0UGFzY2FsQ2FzZVJvdXRlTmFtZShyb3V0ZU5vZGUpXHJcbiAgICAgICAgICAucmVwbGFjZSgvKFthLXpcXGRdKShbQS1aXSkvZywgXCIkMS0kMlwiKVxyXG4gICAgICAgICAgLnRvTG93ZXJDYXNlKCk7XHJcbiAgICAgIH0sXHJcblxyXG4gICAgICBiZWZvcmVXcml0ZUZpbGVzOiAocm9vdCkgPT4ge1xyXG4gICAgICAgIHJvb3QuaW5zZXJ0KFxyXG4gICAgICAgICAgXCIvYXBwcy9lbWFpbC86ZmlsdGVyXCIsXHJcbiAgICAgICAgICBcIi9yZXNvdXJjZXMvanMvcGFnZXMvYXBwcy9lbWFpbC9pbmRleC52dWVcIlxyXG4gICAgICAgICk7XHJcbiAgICAgICAgcm9vdC5pbnNlcnQoXHJcbiAgICAgICAgICBcIi9hcHBzL2VtYWlsLzpsYWJlbFwiLFxyXG4gICAgICAgICAgXCIvcmVzb3VyY2VzL2pzL3BhZ2VzL2FwcHMvZW1haWwvaW5kZXgudnVlXCJcclxuICAgICAgICApO1xyXG4gICAgICB9LFxyXG5cclxuICAgICAgcm91dGVzRm9sZGVyOiBcInJlc291cmNlcy9qcy9wYWdlc1wiLFxyXG4gICAgfSksXHJcbiAgICB2dWUoe1xyXG4gICAgICB0ZW1wbGF0ZToge1xyXG4gICAgICAgIGNvbXBpbGVyT3B0aW9uczoge1xyXG4gICAgICAgICAgaXNDdXN0b21FbGVtZW50OiAodGFnKSA9PlxyXG4gICAgICAgICAgICB0YWcgPT09IFwic3dpcGVyLWNvbnRhaW5lclwiIHx8IHRhZyA9PT0gXCJzd2lwZXItc2xpZGVcIixcclxuICAgICAgICB9LFxyXG5cclxuICAgICAgICB0cmFuc2Zvcm1Bc3NldFVybHM6IHtcclxuICAgICAgICAgIGJhc2U6IG51bGwsXHJcbiAgICAgICAgICBpbmNsdWRlQWJzb2x1dGU6IGZhbHNlLFxyXG4gICAgICAgIH0sXHJcbiAgICAgIH0sXHJcbiAgICB9KSxcclxuICAgIGxhcmF2ZWwoe1xyXG4gICAgICBpbnB1dDogW1wicmVzb3VyY2VzL2pzL21haW4uanNcIl0sXHJcbiAgICAgIHJlZnJlc2g6IHRydWUsXHJcbiAgICB9KSxcclxuICAgIHZ1ZUpzeCgpLCAvLyBEb2NzOiBodHRwczovL2dpdGh1Yi5jb20vdnVldGlmeWpzL3Z1ZXRpZnktbG9hZGVyL3RyZWUvbWFzdGVyL3BhY2thZ2VzL3ZpdGUtcGx1Z2luXHJcbiAgICB2dWV0aWZ5KHtcclxuICAgICAgc3R5bGVzOiB7XHJcbiAgICAgICAgY29uZmlnRmlsZTogXCJyZXNvdXJjZXMvc3R5bGVzL3ZhcmlhYmxlcy9fdnVldGlmeS5zY3NzXCIsXHJcbiAgICAgIH0sXHJcbiAgICB9KSwgLy8gRG9jczogaHR0cHM6Ly9naXRodWIuY29tL2Rpc2hhaXQvdml0ZS1wbHVnaW4tdnVlLW1ldGEtbGF5b3V0cz90YWI9cmVhZG1lLW92LWZpbGVcclxuICAgIE1ldGFMYXlvdXRzKHtcclxuICAgICAgdGFyZ2V0OiBcIi4vcmVzb3VyY2VzL2pzL2xheW91dHNcIixcclxuICAgICAgZGVmYXVsdExheW91dDogXCJkZWZhdWx0XCIsXHJcbiAgICB9KSwgLy8gRG9jczogaHR0cHM6Ly9naXRodWIuY29tL2FudGZ1L3VucGx1Z2luLXZ1ZS1jb21wb25lbnRzI3VucGx1Z2luLXZ1ZS1jb21wb25lbnRzXHJcbiAgICBDb21wb25lbnRzKHtcclxuICAgICAgZGlyczogW1xyXG4gICAgICAgIFwicmVzb3VyY2VzL2pzL0Bjb3JlL2NvbXBvbmVudHNcIixcclxuICAgICAgICBcInJlc291cmNlcy9qcy92aWV3cy9kZW1vc1wiLFxyXG4gICAgICAgIFwicmVzb3VyY2VzL2pzL2NvbXBvbmVudHNcIixcclxuICAgICAgXSxcclxuICAgICAgZHRzOiB0cnVlLFxyXG4gICAgICByZXNvbHZlcnM6IFtcclxuICAgICAgICAoY29tcG9uZW50TmFtZSkgPT4ge1xyXG4gICAgICAgICAgLy8gQXV0byBpbXBvcnQgYFZ1ZUFwZXhDaGFydHNgXHJcbiAgICAgICAgICBpZiAoY29tcG9uZW50TmFtZSA9PT0gXCJWdWVBcGV4Q2hhcnRzXCIpXHJcbiAgICAgICAgICAgIHJldHVybiB7XHJcbiAgICAgICAgICAgICAgbmFtZTogXCJkZWZhdWx0XCIsXHJcbiAgICAgICAgICAgICAgZnJvbTogXCJ2dWUzLWFwZXhjaGFydHNcIixcclxuICAgICAgICAgICAgICBhczogXCJWdWVBcGV4Q2hhcnRzXCIsXHJcbiAgICAgICAgICAgIH07XHJcbiAgICAgICAgfSxcclxuICAgICAgXSxcclxuICAgIH0pLCAvLyBEb2NzOiBodHRwczovL2dpdGh1Yi5jb20vYW50ZnUvdW5wbHVnaW4tYXV0by1pbXBvcnQjdW5wbHVnaW4tYXV0by1pbXBvcnRcclxuICAgIEF1dG9JbXBvcnQoe1xyXG4gICAgICBpbXBvcnRzOiBbXHJcbiAgICAgICAgXCJ2dWVcIixcclxuICAgICAgICBWdWVSb3V0ZXJBdXRvSW1wb3J0cyxcclxuICAgICAgICBcIkB2dWV1c2UvY29yZVwiLFxyXG4gICAgICAgIFwiQHZ1ZXVzZS9tYXRoXCIsXHJcbiAgICAgICAgXCJ2dWUtaTE4blwiLFxyXG4gICAgICAgIFwicGluaWFcIixcclxuICAgICAgXSxcclxuICAgICAgZGlyczogW1xyXG4gICAgICAgIFwiLi9yZXNvdXJjZXMvanMvQGNvcmUvdXRpbHNcIixcclxuICAgICAgICBcIi4vcmVzb3VyY2VzL2pzL0Bjb3JlL2NvbXBvc2FibGUvXCIsXHJcbiAgICAgICAgXCIuL3Jlc291cmNlcy9qcy9jb21wb3NhYmxlcy9cIixcclxuICAgICAgICBcIi4vcmVzb3VyY2VzL2pzL3V0aWxzL1wiLFxyXG4gICAgICAgIFwiLi9yZXNvdXJjZXMvanMvcGx1Z2lucy8qL2NvbXBvc2FibGVzLypcIixcclxuICAgICAgXSxcclxuICAgICAgdnVlVGVtcGxhdGU6IHRydWUsXHJcblxyXG4gICAgICAvLyBcdTIxMzlcdUZFMEYgRGlzYWJsZWQgdG8gYXZvaWQgY29uZnVzaW9uICYgYWNjaWRlbnRhbCB1c2FnZVxyXG4gICAgICBpZ25vcmU6IFtcInVzZUNvb2tpZXNcIiwgXCJ1c2VTdG9yYWdlXCJdLFxyXG4gICAgICBlc2xpbnRyYzoge1xyXG4gICAgICAgIGVuYWJsZWQ6IHRydWUsXHJcbiAgICAgICAgZmlsZXBhdGg6IFwiLi8uZXNsaW50cmMtYXV0by1pbXBvcnQuanNvblwiLFxyXG4gICAgICB9LFxyXG4gICAgfSksIC8vIERvY3M6IGh0dHBzOi8vZ2l0aHViLmNvbS9pbnRsaWZ5L2J1bmRsZS10b29scy90cmVlL21haW4vcGFja2FnZXMvdW5wbHVnaW4tdnVlLWkxOG4jaW50bGlmeXVucGx1Z2luLXZ1ZS1pMThuXHJcbiAgICBWdWVJMThuUGx1Z2luKHtcclxuICAgICAgcnVudGltZU9ubHk6IHRydWUsXHJcbiAgICAgIGNvbXBvc2l0aW9uT25seTogdHJ1ZSxcclxuICAgICAgaW5jbHVkZTogW1xyXG4gICAgICAgIGZpbGVVUkxUb1BhdGgoXHJcbiAgICAgICAgICBuZXcgVVJMKFwiLi9yZXNvdXJjZXMvanMvcGx1Z2lucy9pMThuL2xvY2FsZXMvKipcIiwgaW1wb3J0Lm1ldGEudXJsKVxyXG4gICAgICAgICksXHJcbiAgICAgIF0sXHJcbiAgICB9KSxcclxuICAgIHN2Z0xvYWRlcigpLFxyXG4gIF0sXHJcbiAgZGVmaW5lOiB7IFwicHJvY2Vzcy5lbnZcIjoge30gfSxcclxuICByZXNvbHZlOiB7XHJcbiAgICBhbGlhczoge1xyXG4gICAgICBcIkBjb3JlLXNjc3NcIjogZmlsZVVSTFRvUGF0aChcclxuICAgICAgICBuZXcgVVJMKFwiLi9yZXNvdXJjZXMvc3R5bGVzL0Bjb3JlXCIsIGltcG9ydC5tZXRhLnVybClcclxuICAgICAgKSxcclxuICAgICAgXCJAXCI6IGZpbGVVUkxUb1BhdGgobmV3IFVSTChcIi4vcmVzb3VyY2VzL2pzXCIsIGltcG9ydC5tZXRhLnVybCkpLFxyXG4gICAgICBcIkB0aGVtZUNvbmZpZ1wiOiBmaWxlVVJMVG9QYXRoKFxyXG4gICAgICAgIG5ldyBVUkwoXCIuL3RoZW1lQ29uZmlnLmpzXCIsIGltcG9ydC5tZXRhLnVybClcclxuICAgICAgKSxcclxuICAgICAgXCJAY29yZVwiOiBmaWxlVVJMVG9QYXRoKG5ldyBVUkwoXCIuL3Jlc291cmNlcy9qcy9AY29yZVwiLCBpbXBvcnQubWV0YS51cmwpKSxcclxuICAgICAgXCJAbGF5b3V0c1wiOiBmaWxlVVJMVG9QYXRoKFxyXG4gICAgICAgIG5ldyBVUkwoXCIuL3Jlc291cmNlcy9qcy9AbGF5b3V0c1wiLCBpbXBvcnQubWV0YS51cmwpXHJcbiAgICAgICksXHJcbiAgICAgIFwiQGltYWdlc1wiOiBmaWxlVVJMVG9QYXRoKG5ldyBVUkwoXCIuL3Jlc291cmNlcy9pbWFnZXMvXCIsIGltcG9ydC5tZXRhLnVybCkpLFxyXG4gICAgICBcIkBzdHlsZXNcIjogZmlsZVVSTFRvUGF0aChuZXcgVVJMKFwiLi9yZXNvdXJjZXMvc3R5bGVzL1wiLCBpbXBvcnQubWV0YS51cmwpKSxcclxuICAgICAgXCJAY29uZmlndXJlZC12YXJpYWJsZXNcIjogZmlsZVVSTFRvUGF0aChcclxuICAgICAgICBuZXcgVVJMKFwiLi9yZXNvdXJjZXMvc3R5bGVzL3ZhcmlhYmxlcy9fdGVtcGxhdGUuc2Nzc1wiLCBpbXBvcnQubWV0YS51cmwpXHJcbiAgICAgICksXHJcbiAgICAgIFwiQGRiXCI6IGZpbGVVUkxUb1BhdGgoXHJcbiAgICAgICAgbmV3IFVSTChcIi4vcmVzb3VyY2VzL2pzL3BsdWdpbnMvZmFrZS1hcGkvaGFuZGxlcnMvXCIsIGltcG9ydC5tZXRhLnVybClcclxuICAgICAgKSxcclxuICAgICAgXCJAYXBpLXV0aWxzXCI6IGZpbGVVUkxUb1BhdGgoXHJcbiAgICAgICAgbmV3IFVSTChcIi4vcmVzb3VyY2VzL2pzL3BsdWdpbnMvZmFrZS1hcGkvdXRpbHMvXCIsIGltcG9ydC5tZXRhLnVybClcclxuICAgICAgKSxcclxuICAgIH0sXHJcbiAgfSxcclxuICBidWlsZDoge1xyXG4gICAgY2h1bmtTaXplV2FybmluZ0xpbWl0OiA1MDAwLFxyXG4gIH0sXHJcbiAgb3B0aW1pemVEZXBzOiB7XHJcbiAgICBleGNsdWRlOiBbXCJ2dWV0aWZ5XCJdLFxyXG4gICAgZW50cmllczogW1wiLi9yZXNvdXJjZXMvanMvKiovKi52dWVcIl0sXHJcbiAgfSxcclxuICBzZXJ2ZXI6IHtcclxuICAgIHdhdGNoOiB7XHJcbiAgICAgIHVzZVBvbGxpbmc6IGZhbHNlLFxyXG4gICAgfSxcclxuICB9LFxyXG59KTtcclxuIl0sCiAgIm1hcHBpbmdzIjogIjtBQUErUCxPQUFPLG1CQUFtQjtBQUN6UixPQUFPLFNBQVM7QUFDaEIsT0FBTyxZQUFZO0FBQ25CLE9BQU8sYUFBYTtBQUNwQixTQUFTLHFCQUFxQjtBQUM5QixPQUFPLGdCQUFnQjtBQUN2QixPQUFPLGdCQUFnQjtBQUN2QjtBQUFBLEVBQ0U7QUFBQSxFQUNBO0FBQUEsT0FDSztBQUNQLE9BQU8sZUFBZTtBQUN0QixTQUFTLG9CQUFvQjtBQUM3QixPQUFPLGlCQUFpQjtBQUN4QixPQUFPLGFBQWE7QUFDcEIs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
