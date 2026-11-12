<template>
  <div v-for="category in categories" :key="category.title">
    <h3>{{ category.title }}</h3>
    <table>
      <thead>
        <tr>
          <th>Plugin</th>
          <th>Status</th>
          <th>Documentation</th>
          <th>Comment</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="plugin in category.plugins" :key="plugin.url">
          <td>
            <a :href="withBase(plugin.url)">{{ plugin.title }}</a>
          </td>
          <td>{{ plugin.status }}</td>
          <td>{{ plugin.doc }}</td>
          <td>{{ plugin.comment }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue"
import { useData, withBase, type DefaultTheme } from "vitepress"
import { data } from "../plugins.data.mts"

const { theme } = useData()

const linksOf = (items: DefaultTheme.SidebarItem[]): string[] =>
  items.flatMap((item) => [
    ...(item.link ? [item.link] : []),
    ...linksOf(item.items ?? []),
  ])

// Plugins listed in the sidebar follow its order.
// Plugins hidden from the sidebar keep their status order and come last.
const categories = computed(() => {
  const sidebarLinks = linksOf(theme.value.sidebar)
  const sidebarPosition = (url: string): number => {
    const position = sidebarLinks.indexOf(url.replace(/\.html$/, ""))
    return position === -1 ? sidebarLinks.length : position
  }

  return data.map((category) => ({
    ...category,
    plugins: [...category.plugins].sort(
      (first, second) =>
        sidebarPosition(first.url) - sidebarPosition(second.url),
    ),
  }))
})
</script>
