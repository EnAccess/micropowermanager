<template>
  <div
    v-if="paginator && paginator.totalEntries > 0"
    class="md-table-pagination"
  >
    <template v-if="paginator.totalPage >= 5">
      <span class="md-table-pagination-label">Go to Page:</span>

      <md-field>
        <md-select
          v-model="goPage"
          md-dense
          md-class="md-pagination-select"
          @md-selected="changePage(goPage)"
        >
          <md-option
            v-for="page in paginator.totalPage"
            :key="page"
            :value="page"
          >
            {{ page }}
          </md-option>
        </md-select>
      </md-field>
    </template>

    <template v-if="show_per_page">
      <span class="md-table-pagination-label">Rows per page:</span>

      <md-field>
        <md-select
          v-model="perPage"
          md-dense
          md-class="md-pagination-select"
          @md-selected="defaultItemsPerPage(perPage)"
        >
          <md-option value="15">15</md-option>
          <md-option value="50">50</md-option>
          <md-option value="100">100</md-option>
          <md-option value="200">200</md-option>
        </md-select>
      </md-field>
    </template>

    <span>
      {{
        $tc("phrases.paginateLabels", 1, {
          from: paginator.from,
          to: paginator.to,
          total: paginator.totalEntries,
        })
      }}
    </span>

    <md-button
      class="md-icon-button md-table-pagination-previous"
      @click="changePage(--paginator.currentPage)"
      :disabled="paginator.currentPage === 1"
    >
      <md-icon>keyboard_arrow_left</md-icon>
    </md-button>

    <md-button
      class="md-icon-button md-table-pagination-next"
      @click="changePage(++paginator.currentPage)"
      :disabled="paginator.currentPage === paginator.totalPage"
    >
      <md-icon>keyboard_arrow_right</md-icon>
    </md-button>
  </div>
</template>

<script>
import { Paginator } from "@/Helpers/Paginator"
import { EventBus } from "./eventbus"
import { notify } from "@/mixins/notify"
export default {
  name: "Paginate",
  mixins: [notify],
  props: {
    paginatorReference: Paginator,
    callback: {},
    subscriber: String,
    route_name: String,
    show_per_page: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      loading: false,
      currentFrom: 0,
      currentTo: 0,
      total: 0,
      currentPage: 0,
      totalPages: 0,
      paginator: null,
      term: {},
      threeDots: false,
      perPage: 15,
      goPage: null,
    }
  },
  mounted() {
    //load the first page
    let pageNumber = this.route_name ? this.$route.query.page : 1
    this.term = this.route_name ? this.$route.query : {}
    this.loadPage(pageNumber)
    EventBus.$on("loadPage", this.eventLoadPage)
  },
  destroyed() {
    this.paginator = null
  },
  watch: {
    $route() {
      if (this.route_name) {
        this.loadPage(this.currentPage)
      }
    },
    paginatorReference: {
      handler(newPaginator) {
        this.paginator = newPaginator
      },
      deep: true,
      immediate: true,
    },
  },
  methods: {
    changePage(pageNumber) {
      if (this.goPage !== pageNumber) this.goPage = pageNumber
      if (!isNaN(pageNumber)) {
        if (pageNumber > this.paginator.totalPage) {
          this.alertNotify(
            "error",
            "Page Number is bigger than Total Pages Count",
          )
          return
        }
        this.currentPage = pageNumber
        if (this.route_name) {
          this.$router
            .push({
              query: Object.assign({}, this.term, {
                page: pageNumber,
                per_page: this.paginator.perPage,
              }),
            })
            .catch((error) => {
              if (error.name !== "NavigationDuplicated") {
                throw error
              }
            })
        } else {
          this.loadPage(pageNumber)
        }
      } else {
        this.alertNotify("error", "Page is not a Number")
      }
    },
    eventLoadPage(paginator, term = {}) {
      this.term = term
      this.paginator = paginator
      this.loadPage(1)
    },
    defaultItemsPerPage(data) {
      this.paginator.perPage = data.target.value
      this.loadPage(this.paginator.currentPage)
    },
    loadPage(pageNumber) {
      if (this.loading) {
        return
      }
      if (this.goPage !== pageNumber) this.goPage = pageNumber
      this.loading = true
      this.paginator
        .loadPage(pageNumber, this.term)
        .then((response) => {
          this.loading = false
          EventBus.$emit("pageLoaded", this.subscriber, response.data)
        })
        .catch((error) => {
          this.loading = false
          if (error.response && error.response.status === 403) {
            console.warn(
              `Permission denied for ${this.subscriber}:`,
              error.message,
            )
            // Emit empty data to trigger empty state instead of infinite loading
            EventBus.$emit("pageLoaded", this.subscriber, [])
          } else {
            this.alertNotify(
              "error",
              error.response?.data?.message || error.message,
            )
            // Emit empty data to prevent infinite loading
            EventBus.$emit("pageLoaded", this.subscriber, [])
          }
        })
    },
  },
}
</script>

<style lang="scss" scoped>
.md-table-pagination {
  height: 56px;
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: flex-end;
  border-top: 0px solid;
  font-size: 12px;

  .md-table-pagination-previous {
    margin-right: 2px;
    margin-left: 18px;
  }

  .md-field {
    width: 48px;
    min-width: 36px;
    margin: -16px 24px 0 32px;

    &:after,
    &:before {
      display: none;
    }

    .md-select-value {
      font-size: 13px;
    }
  }
}

.md-menu-content.md-pagination-select {
  max-width: 82px;
  min-width: 56px;
  margin-top: 5px;
}

// Workaround to fight global styling from SideBar.vue
::v-deep(.md-icon.md-theme-default.md-icon-image svg) {
  fill: rgba(0, 0, 0, 0.87) !important;
}
</style>
