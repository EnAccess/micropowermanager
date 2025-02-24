<template>
  <div
    v-if="paginator && paginator.totalPage > 1"
    class="md-layout md-gutter md-size-100 pagination-area"
  >
    <div class="md-layout-item md-size-25 pagination-entry">
      {{
        $tc("phrases.paginateLabels", 1, {
          from: paginator.from,
          to: paginator.to,
          total: paginator.totalEntries,
        })
      }}
    </div>
    <div
      class="md-layout-item md-size-20 pagination-per-page"
      v-if="show_per_page"
    ></div>
    <div
      class="md-layout-item"
      :class="{
        'md-size-70': !show_per_page,
        'md-size-50': show_per_page,
      }"
    >
      <div class="md-layout pagination">
        <span v-if="show_per_page">{{ $tc("phrases.perPage") }}:</span>
        <select
          v-if="show_per_page"
          name="per_page"
          id="per_page"
          @change="defaultItemsPerPage"
        >
          <option value="15">15</option>
          <option value="25">25</option>
          <option value="30">30</option>
          <option value="50">50</option>
          <option value="100">100</option>
          <option value="200">200</option>
          <option value="300">300</option>
        </select>
        <input type="number" v-model="goPage" v-if="paginator.totalPage >= 5" />
        <button @click="changePage(goPage)" v-if="paginator.totalPage >= 5">
          Go
        </button>
        <a
          href="javascript:void(0)"
          :class="{ disabled: paginator.currentPage === 1 }"
          @click="changePage(1)"
        >
          <md-icon :class="{ disabled: paginator.currentPage === 1 }">
            first_page
          </md-icon>
        </a>
        <a
          href="javascript:void(0)"
          :class="{ disabled: paginator.currentPage === 1 }"
          @click="changePage(--paginator.currentPage)"
        >
          <md-icon :class="{ disabled: paginator.currentPage === 1 }">
            chevron_left
          </md-icon>
        </a>
        <span>
          {{ paginator.currentPage }} of
          {{ formatTotalPages(paginator.totalPage) }}
        </span>
        <a
          href="javascript:void(0)"
          :class="{
            disabled: paginator.currentPage === paginator.totalPage,
          }"
          @click="changePage(++paginator.currentPage)"
        >
          <md-icon
            :class="{
              disabled: paginator.currentPage === paginator.totalPage,
            }"
          >
            chevron_right
          </md-icon>
        </a>
        <a
          href="javascript:void(0)"
          :class="{
            disabled: paginator.currentPage === paginator.totalPage,
          }"
          @click="changePage(paginator.totalPage)"
        >
          <md-icon
            :class="{
              disabled: paginator.currentPage === paginator.totalPage,
            }"
          >
            last_page
          </md-icon>
        </a>
      </div>
    </div>
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
      paginator: this.paginatorReference,
      term: {},
      threeDots: false,
      perPage: 15,
      goPage: null,
    }
  },
  mounted() {
    //load the first page
    let pageNumber = this.$route.query.page
    this.term = this.$route.query
    this.loadPage(pageNumber)
    EventBus.$on("loadPage", this.eventLoadPage)
  },
  destroyed() {
    this.paginator = null
  },
  watch: {
    $route() {
      this.loadPage(this.currentPage)
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
    defaultCallback(data = null) {
      console.log("default callback with", data)
    },
    loadPage(pageNumber) {
      if (this.loading) {
        return
      }
      if (this.goPage !== pageNumber) this.goPage = pageNumber
      this.loading = true
      this.paginator.loadPage(pageNumber, this.term).then((response) => {
        this.loading = false
        EventBus.$emit("pageLoaded", this.subscriber, response.data)
      })
    },
    formatTotalPages(pageNumber) {
      return pageNumber.toLocaleString()
    },
  },
}
</script>

<style scoped>
.pagination-area {
  width: 100%;
  margin: 0;
  position: absolute;
}
.pagination-entry {
  font-style: italic;
  margin: 8px;
}
.pagination-per-page {
  font-style: italic;
  margin: 8px;
  right: 0 !important;
  float: right;
}
.pagination {
  display: inline-block;
  margin-top: 2px;
  right: 0 !important;
  float: right;
}
.pagination a,
span,
input[type="number"],
button,
select {
  max-width: 90px;
  color: black !important;
  float: left;
  padding: 1px 10px;
  text-decoration: none;
  transition: background-color 0.3s;
  margin: 8px 2px;
  height: 25px;
}
.pagination input[type="number"]:focus {
  border: 2px solid #555;
}
.pagination a:hover {
  background-color: #ddd;
  color: #2f0d0b !important;
}
.pagination .disabled {
  pointer-events: none;
  color: #ccc !important;
}
</style>
