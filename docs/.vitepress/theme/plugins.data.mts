import { createContentLoader } from "vitepress"

export interface PluginPage {
  title: string
  url: string
  status?: string
  doc?: string
  comment?: string
}

export interface PluginCategory {
  title: string
  order: number
  plugins: PluginPage[]
}

declare const data: PluginCategory[]
export { data }

const STATUS_ORDER = [
  "✅ Ready",
  "⚠️ Requires refactoring",
  "❌ Broken",
  "🌑 No longer supported",
]

const statusRank = (status?: string): number => {
  const rank = STATUS_ORDER.indexOf(status ?? "")
  return rank === -1 ? STATUS_ORDER.length : rank
}

const titleOf = (source: string): string =>
  source.replace(/^---\n[\s\S]*?\n---\n/, "").match(/^# (.+)$/m)?.[1] ?? ""

export default createContentLoader("integrations-guide/*/*.md", {
  includeSrc: true,
  transform(pages): PluginCategory[] {
    const categories = new Map<string, PluginCategory>()
    const categoryOf = (url: string): PluginCategory => {
      const directory = url.slice(0, url.lastIndexOf("/") + 1)
      if (!categories.has(directory)) {
        categories.set(directory, { title: "", order: 0, plugins: [] })
      }
      return categories.get(directory)!
    }

    for (const { url, src, frontmatter } of pages) {
      const category = categoryOf(url)
      const title = titleOf(src ?? "")

      // Each category folder has an index page that only names and orders it.
      if (url.endsWith("/")) {
        Object.assign(category, { title, order: frontmatter.order ?? 0 })
        continue
      }

      category.plugins.push({
        title,
        url,
        status: frontmatter.mpm_plugin_status,
        doc: frontmatter.mpm_plugin_doc,
        comment: frontmatter.mpm_plugin_comment,
      })
    }

    for (const category of categories.values()) {
      category.plugins.sort(
        (first, second) =>
          statusRank(first.status) - statusRank(second.status) ||
          first.title.localeCompare(second.title),
      )
    }

    return [...categories.values()].sort(
      (first, second) =>
        first.order - second.order || first.title.localeCompare(second.title),
    )
  },
})
