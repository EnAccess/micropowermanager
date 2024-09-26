<template>
  <div v-if="url" class="md-layout md-gutter md-size-100 paginate-area">
    <div class="md-layout-item md-size-33">
      <div
        class="col-xs-12 hidden-xs"
        :class="
          show_per_page === true ? 'col-sm-4 col-lg-5' : 'col-sm-6 col-lg-6'
        "
      >
        <div
          class="dataTables_info"
          id="datatable_col_reorder_info2"
          role="status"
          aria-live="polite"
        >
          Showing {{ paginateService.paginator.from }} to
          {{ paginateService.paginator.to }} of
          {{ paginateService.paginator.totalEntries }} entries
        </div>
      </div>
    </div>

    <div class="md-layout-item md-size-33">
      <div class="col-sm-2 col-lg-1 col-xs-6" v-if="show_per_page === true">
        <div
          style="float: right"
          class="dataTables_info"
          id="datatable_col_reorder_info"
          role="status"
          aria-live="polite"
        >
          Per Page
          <select name="per_page" id="per_page" @change="defaultItemsPerPage">
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="30">30</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="200">200</option>
            <option value="300">300</option>
          </select>
        </div>
      </div>
    </div>

    <div class="md-layout-item md-size-33">
      <div class="col-sm-6 col-xs-12">
        <div
          class="dataTables_paginate paging_simple_numbers"
          id="datatable_col_reorder_paginate"
        >
          <ul class="pagination pagination-sm">
            <li
              :class="
                paginateService.paginator.currentPage > 1
                  ? 'paginate_button previous'
                  : ' paginate_button previous-disabled'
              "
              id="datatable_col_reorder_previous"
            >
              <a
                v-if="!loading"
                href="javascript:void(0);"
                aria-controls="datatable_col_reorder"
                data-dt-idx="0"
                tabindex="0"
                @click="loadPage(--paginateService.paginator.currentPage)"
              >
                Previous
              </a>
              <a href="javascript:void(0);" disabled="disabled" v-else>
                Previous
              </a>
            </li>
            <template
              v-for="(page, index) in paginateService.paginator.totalPage"
            >
              <li
                :key="index"
                :class="
                  page === paginateService.paginator.currentPage
                    ? ' active'
                    : ''
                "
                v-if="
                  paginateService.paginator.currentPage - index < 4 &&
                  paginateService.paginator.currentPage - index > 0
                "
              >
                <a
                  v-if="
                    index < paginateService.paginator.currentPage + 2 &&
                    index > paginateService.paginator.currentPage - 4
                  "
                  href="javascript:void(0);"
                  @click="loadPage(page)"
                >
                  {{ page }}
                </a>

                <a
                  v-else-if="
                    index === 2 + paginateService.paginator.currentPage
                  "
                >
                  ...
                </a>
                <a
                  v-else-if="
                    index > Math.abs(paginateService.paginator.totalPage - 3)
                  "
                  href="javascript:void(0);"
                  @click="loadPage(page)"
                >
                  {{ page }}
                </a>
              </li>
            </template>

            <li
              :class="
                paginateService.paginator.currentPage <
                paginateService.paginator.totalPage
                  ? 'paginate_button next'
                  : 'paginate_button next-disabled'
              "
              id="datatable_col_reorder_next"
            >
              <a
                v-if="!loading"
                href="javascript:void(0);"
                aria-controls="datatable_col_reorder"
                data-dt-idx="8"
                tabindex="0"
                @click="loadPage(++paginateService.paginator.currentPage)"
              >
                Next
              </a>
              <a href="javascript:void(0);" v-else>Next</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { PaginatorLegacyService } from "@/services/PaginatorLegacyService"
import { EventBus } from "@/shared/eventbus"

/**
 * Legacy Paginator component
 *
 * DEPRECATED: Used in some older plugins and components.
 * New components should use to more flexible Paginate component
 * for pagination.
 */
export default {
  name: "Paginator",
  props: {
    url: {
      default: null,
    },
    subscriber: {
      type: String,
      default: "",
    },
    route_name: {
      type: String,
      default: "",
    },
    show_per_page: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      paginateService: new PaginatorLegacyService(this.url),
      loading: false,
      term: {},
      threeDots: false,
    }
  },
  mounted() {
    let pageNumber = this.$route.query.page
    this.loadPage(pageNumber)
    EventBus.$on("loadPage", this.eventLoadPage)
  },
  methods: {
    eventLoadPage(paginatorUrl, term = {}) {
      this.term = term
      this.paginateService = new PaginatorLegacyService(paginatorUrl)
      this.loadPage(1)
    },
    defaultItemsPerPage(data) {
      this.paginateService.paginator.perPage = data.target.value
      this.loadPage(this.paginateService.paginator.currentPage)
    },
    async loadPage(pageNumber = 1) {
      if (this.loading) return
      this.loading = true
      await this.paginateService.loadPage(pageNumber, this.term)
      if (pageNumber) {
        this.$router
          .push({
            query: Object.assign({}, this.$route.query, {
              page: pageNumber,
              per_page: this.paginateService.paginator.perPage,
            }),
          })
          .catch((error) => {
            if (error.name !== "NavigationDuplicated") {
              throw error
            }
          })
      }
      this.loading = false
      EventBus.$emit(
        "pageLoaded",
        this.subscriber,
        this.paginateService.paginator.data,
      )
    },
  },
}
</script>

<style scoped lang="scss">
.paginate-area {
  width: 100% !important;
}

.pagination {
  color: #ac2925;
  list-style: none;
  display: flex;

  li {
    list-style: none;
    display: inline-flex;
    padding: 5px;
    margin: 1px;
    background-color: #f7f7f7;
  }

  .active {
    background-color: #dddddd;
  }
}

.dataTables_info {
  padding-top: 9px;
  font-size: 13px;
  font-weight: 700;
  font-style: italic;
  color: #969696;
}

.dataTables_paginate {
  float: right;
  margin: 0;
}

.dataTables_paginate ul.pagination {
  margin: 2px 0;
  white-space: nowrap;
}

.dataTables_paginate {
  float: right;
  margin: 0;
}

.pagination {
  display: inline-flex;
  padding-left: 0;
  margin: 18px 0;
  border-radius: 2px;
}

.pagination > li {
  display: inline;
}

.pagination > li > a,
.pagination > li > span {
  position: relative;
  float: left;
  padding: 6px 12px;
  line-height: 1.42857143;
  text-decoration: none;
  color: #3276b1;
  background-color: #fff;
  border: 1px solid #ddd;
  margin-left: -1px;
}

.pagination > li:first-child > a,
.pagination > li:first-child > span {
  margin-left: 0;
  border-bottom-left-radius: 2px;
  border-top-left-radius: 2px;
}

.pagination > li:last-child > a,
.pagination > li:last-child > span {
  border-bottom-right-radius: 2px;
  border-top-right-radius: 2px;
}

.pagination > li > a:focus,
.pagination > li > a:hover,
.pagination > li > span:focus,
.pagination > li > span:hover {
  z-index: 2;
  color: #214e75;
  background-color: #eee;
  border-color: #ddd;
}

.pagination > .active > a,
.pagination > .active > a:focus,
.pagination > .active > a:hover,
.pagination > .active > span,
.pagination > .active > span:focus,
.pagination > .active > span:hover {
  z-index: 3;
  color: #fff;
  background-color: #3276b1;
  border-color: #3276b1;
  cursor: default;
}

.pagination > .disabled > a,
.pagination > .disabled > a:focus,
.pagination > .disabled > a:hover,
.pagination > .disabled > span,
.pagination > .disabled > span:focus,
.pagination > .disabled > span:hover {
  color: #999;
  background-color: #fff;
  border-color: #ddd;
  cursor: not-allowed;
}

.pagination-lg > li > a,
.pagination-lg > li > span {
  padding: 10px 16px;
  font-size: 17px;
  line-height: 1.33;
}

.pagination-lg > li:first-child > a,
.pagination-lg > li:first-child > span {
  border-bottom-left-radius: 3px;
  border-top-left-radius: 3px;
}

.pagination-lg > li:last-child > a,
.pagination-lg > li:last-child > span {
  border-bottom-right-radius: 3px;
  border-top-right-radius: 3px;
}

.pagination-sm > li > a,
.pagination-sm > li > span {
  padding: 5px 10px;
  font-size: 12px;
  line-height: 1.5;
}

.pagination-sm > li:first-child > a,
.pagination-sm > li:first-child > span {
  border-bottom-left-radius: 2px;
  border-top-left-radius: 2px;
}

.pagination-sm > li:last-child > a,
.pagination-sm > li:last-child > span {
  border-bottom-right-radius: 2px;
  border-top-right-radius: 2px;
}

.pagination.pagination-alt > li > a {
  box-shadow: none;
  -moz-box-shadow: none;
  -webkit-box-shadow: none;
  border: none;
  margin-left: -1px;
}

.pagination.pagination-alt > li:first-child > a {
  padding-left: 0;
}

.pagination > li > a,
.pagination > li > span {
  box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.05);
  -moz-box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.05);
  -webkit-box-shadow: inset 0 -2px 0 rgba(0, 0, 0, 0.05);
}

.previous-disabled {
  pointer-events: none;
}

.next-disabled {
  pointer-events: none;
}
</style>
