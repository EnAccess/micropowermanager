<template>
  <div class="breadcrumb" :key="renderKey">
    <ul v-if="breadcrumbList">
      <li
        v-for="(breadcrumb, index) in breadcrumbList"
        :key="index"
        @click="routeTo(index)"
        :class="{ linked: breadcrumbList.length !== index }"
      >
        <div v-if="breadcrumb.level === 'detail'">
          <u>
            {{ translateItem(breadcrumb.name) }}/{{ breadcrumb.targetParam }}
          </u>
        </div>
        <div v-else>
          <u>{{ translateItem(breadcrumb.name) }}</u>
        </div>
      </li>
    </ul>
  </div>
</template>

<script>
import { translateItem } from "@/Helpers/TranslateItem"

export default {
  name: "Breadcrumb",
  data() {
    return {
      breadcrumbList: [],
      breadcrumbListState: [],
      prevRoute: [],
      renderKey: 0,
      translateItem: translateItem,
    }
  },
  created() {
    this.breadcrumbListState = this.$store.getters["breadcrumb/getBreadcrumb"]
    if (
      this.breadcrumbListState["breadcrumb"] &&
      this.breadcrumbListState["breadcrumb"].length > 0
    ) {
      this.breadcrumbList = this.breadcrumbListState["breadcrumb"]
    } else {
      this.updateList()
    }
  },
  watch: {
    $route() {
      this.updateList()
    },
  },
  methods: {
    routeTo(index) {
      if (this.breadcrumbList[index].link) {
        if (this.breadcrumbList[index].level === "detail") {
          this.$router.push(this.breadcrumbList[index].fullPath)
        } else {
          this.$router.push(this.breadcrumbList[index].link)
        }
      }
    },
    reRenderBreadcrumb() {
      this.renderKey += 1
    },
    storeBreadcrumb() {
      this.$store
        .dispatch("breadcrumb/setBreadcrumb", this.breadcrumbList)
        .then(() => {})
        .catch((err) => err)
    },
    updateList() {
      if (this.$route.meta.breadcrumb) {
        let currentBreadcrumb = this.$route.meta.breadcrumb
        if (this.$route.meta.breadcrumb.level === "base") {
          this.breadcrumbList = []
        } else {
          currentBreadcrumb.fullPath =
            currentBreadcrumb.link +
            "/" +
            this.$route.params[currentBreadcrumb.target]
          currentBreadcrumb.targetParam =
            this.$route.params[currentBreadcrumb.target]
        }
        const breadCrumbAlreadyVisited = this.breadcrumbList.findIndex(
          (breadcrumbItem) =>
            breadcrumbItem.fullPath === currentBreadcrumb.fullPath,
        )
        if (breadCrumbAlreadyVisited !== -1) {
          this.breadcrumbList = this.breadcrumbList.slice(
            0,
            breadCrumbAlreadyVisited + 1,
          )
        } else {
          this.breadcrumbList.push(currentBreadcrumb)
        }
      } else {
        this.breadcrumbList = []
      }
      this.reRenderBreadcrumb()
      this.storeBreadcrumb()
    },
  },
}
</script>

<style scoped>
.breadcrumb {
}
ul {
  display: flex;
  justify-content: center;
  list-style-type: none;
  margin: 0;
  padding: 0;
}
ul > li {
  display: flex;
  float: left;
  height: 10px;
  width: auto;
  color: white;
  font-style: italic;
  font-weight: bold;
  font-size: 1em;
  cursor: default;
  align-items: center;
}
ul > li:not(:last-child)::after {
  content: ">";
  float: right;
  font-size: 0.8em;
  margin: 0 0.5em;
  color: whitesmoke;
  cursor: default;
}
.linked {
  cursor: pointer;
  font-size: 1em;
  font-weight: bold;
}
</style>
