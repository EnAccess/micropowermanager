import { defineConfig } from "vitepress"
import { generateSidebar } from "vitepress-sidebar"
import { withMermaid } from "vitepress-plugin-mermaid"

// https://vitepress-sidebar.jooy2.com/getting-started
const vitepressSidebarOptions = {
  excludePattern: ["README.md"],
  excludeFilesByFrontmatterFieldName: "exclude",
  sortMenusByFrontmatterOrder: true,
  useFolderTitleFromIndexFile: true,
  useTitleFromFileHeading: true,
  useTitleFromFrontmatter: true,
}

// https://vitepress.dev/reference/site-config
export default withMermaid(
  defineConfig({
    title: "MicroPowerManager",
    description: "Documentation for the MicroPowerManager",

    head: [
      ["link", { rel: "icon", type: "image/png", href: "favicon.png" }],
      [
        "script",
        {
          async: "",
          src: "https://www.googletagmanager.com/gtag/js?id=G-KJX9KM0WPR",
        },
      ],
      [
        "script",
        {},
        `window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-KJX9KM0WPR');`,
      ],
    ],

    // For hosting on Github pages
    // https://vitepress.dev/guide/deploy#github-pages
    // base: "/micropowermanager/",
    vite: {
      publicDir: ".public",
    },
    srcExclude: ["README.md"],
    ignoreDeadLinks: "localhostLinks",

    markdown: {
      math: true,
    },

    themeConfig: {
      // https://vitepress.dev/reference/default-theme-config
      logo: "/mpmlogo_raw.png",
      nav: [
        { text: "Home", link: "/" },
        { text: "Docs", link: "/get-started" },
      ],
      footer: {
        message: "Built with VitePress ❤️.",
        copyright: `Copyright © ${new Date().getFullYear()} EnAccess.`,
      },

      sidebar: generateSidebar(vitepressSidebarOptions),

      socialLinks: [
        {
          icon: "github",
          link: "https://github.com/EnAccess/micropowermanager",
        },
      ],

      search: {
        provider: "local",
      },
    },
  }),
)
