import { defineConfig } from "vitepress"
import { withMermaid } from "vitepress-plugin-mermaid"
import { withSidebar } from "vitepress-sidebar"

// https://vitepress-sidebar.jooy2.com/getting-started
const vitepressSidebarOptions = {
  excludePattern: ["README.md", "roadmap.md"],
  excludeFilesByFrontmatterFieldName: "exclude_from_sidebar",
  sortMenusByFrontmatterOrder: true,
  useFolderTitleFromIndexFile: true,
  useTitleFromFileHeading: true,
  useTitleFromFrontmatter: true,
  collapsed: true,
  collapseDepth: 2,
  // Currently, we don't have a third level.
  // So all folders start expanded.
  collapseFromLevel: 3,
}

const footerLink = (href: string, label: string) =>
  `<a href="${href}">${label}</a>`

// https://vitepress.dev/reference/site-config
export default withMermaid(
  defineConfig(
    withSidebar(
      {
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
          // vitepress-plugin-mermaid does not pre-bundle for the dev server.
          // see: https://github.com/emersonbottero/vitepress-plugin-mermaid/issues/98
          optimizeDeps: {
            include: ["fastdom", "fastdom/extensions/fastdom-promised.js"],
          },
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
            { text: "Cloud", link: "/cloud" },
            { text: "Roadmap", link: "/roadmap" },
          ],
          footer: {
            message: [
              footerLink("https://vitepress.dev/", "Built with VitePress ❤️"),
              footerLink("/legal/legal-disclosure", "Legal disclosure"),
              footerLink("/legal/privacy-policy", "Privacy Policy"),
              footerLink("/legal/cookies-policy", "Cookies Policy"),
            ].join("  |  "),
            copyright: `Copyright © ${new Date().getFullYear()} EnAccess.`,
          },

          socialLinks: [
            {
              icon: "github",
              link: "https://github.com/EnAccess/micropowermanager",
            },
            {
              icon: "discord",
              link: "https://discord.osea-community.org/",
            },
          ],

          search: {
            provider: "local",
          },
        },
      },
      vitepressSidebarOptions,
    ),
  ),
)
