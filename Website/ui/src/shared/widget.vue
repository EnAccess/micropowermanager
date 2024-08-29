<template>
  <div>
    <md-toolbar
      v-if="title !== null"
      :data-color="color"
      class="md-dense chic"
      md-elevation="3"
    >
      <div class="tabs">
        <slot name="tabbar"></slot>
      </div>
      <md-icon style="color: white">list</md-icon>
      <div class="md-toolbar-section-start">
        <div class="md-subheading">{{ title }}</div>
      </div>
      <div class="md-toolbar-section-end">
        <div class="search-area">
          <div class="search-input" v-if="search">
            <div class="md-layout md-gutter">
              <md-field>
                <label style="color: white !important">
                  {{ $tc("words.search") }} ..
                </label>
                <md-input
                  style="color: white !important"
                  v-model="searchTerm"
                ></md-input>
                <div v-if="searching">
                  <span>
                    {{ $tc("phrases.searchResultFor") }}:
                    <small>{{ searchTerm }}</small>
                  </span>
                  <md-button class="md-icon-button" @click="showAllEntries">
                    <md-icon class="pointer">cancel</md-icon>
                  </md-button>
                </div>
                <div v-else>
                  <md-icon style="color: white">search</md-icon>
                </div>
              </md-field>
            </div>
          </div>
        </div>
        <md-button
          :class="setButtonColor()"
          @click="widgetAction"
          class="md-icon-button md-dense md-raised"
          v-if="buttonText !== null"
        >
          <md-tooltip md-direction="top">{{ buttonText }}</md-tooltip>
          <md-icon>{{ buttonIcon }}</md-icon>
        </md-button>
        <md-button
          v-if="showRefreshButton"
          @click="refreshButtonClicked"
          class="md-icon-button md-dense md-raised"
          :class="{ 'refresh-button': isActive }"
        >
          <md-tooltip md-direction="top">
            {{ $tc("words.refresh") }}
          </md-tooltip>
          <md-icon>cached</md-icon>
        </md-button>
      </div>
    </md-toolbar>
    <md-card>
      <md-card-content :class="{ nopadding: title !== null }">
        <div v-if="showData">
          <slot></slot>
        </div>
        <div v-else name="emptyState">
          <div v-if="showEmptyState" name="emptyState" class="empty-state">
            <md-empty-state
              :md-icon="icon"
              :md-description="emptyStateDescription"
              :md-label="getEmptyStateLabel"
            >
              <md-button
                v-if="button && emptyStateCreateButton"
                @click="widgetAction"
                class="md-primary md-raised"
              >
                {{ getEmptyStateButtonText }}
              </md-button>
            </md-empty-state>
          </div>
          <div v-else class="loading-state">
            <div>
              <img src="@/assets/spinner/spinner.gif" alt="" />
            </div>
          </div>
        </div>
      </md-card-content>
    </md-card>
    <md-toolbar class="md-dense" md-elevation="1" v-if="paginator">
      <paginate
        :paginatorReference="paginator"
        :route_name="route_name"
        :show_per_page="show_per_page"
        :subscriber="subscriber"
        v-if="paginator"
        :key="resetKey"
      ></paginate>
    </md-toolbar>
  </div>
</template>

<script>
import { Paginator } from "@/Helpers/Paginator"
import { EventBus } from "./eventbus"
import Paginate from "./Paginate"

const debounce = require("debounce")
export default {
  name: "Widget",
  components: { Paginate },

  props: {
    emptyStateCreateButton: {
      type: Boolean,
      default: true,
    },
    emptyStateDescription: {
      type: String,
      default: null,
    },
    color: {
      type: String,
      default: "default",
    },
    buttonIcon: {
      type: String,
      default: "add",
    },
    showRefreshButton: {
      type: Boolean,
      default: false,
    },
    title: {
      type: String,
      default: null,
    },
    id: String,
    button: Boolean,
    buttonText: {
      type: String,
      default: null,
    },
    buttonColor: String,
    paginator: Paginator,
    search: {},
    subscriber: {
      type: String,
    },
    route_name: String,
    headless: {
      type: Boolean,
      default: false,
    },
    show_per_page: {
      type: Boolean,
      default: false,
    },
    resetKey: {
      default: 0,
    },
  },

  mounted() {
    //listen for a remote trigger for ending the search
    EventBus.$on("search.end", this.cancelSearching)
    EventBus.$on("hideEmptyStateArea", this.hideEmptyStateArea)
    if (this.subscriber === null || this.subscriber === undefined) {
      return (this.showData = true)
    } else {
      EventBus.$on("widgetContentLoaded", this.checkDataLength)
    }
  },
  beforeDestroy() {
    EventBus.$off("search.end", this.cancelSearching)
  },
  data() {
    return {
      searching: false,
      searchTerm: "",
      icon: "post_add",
      showEmptyState: false,
      showData: false,
      isActive: false,
    }
  },
  methods: {
    hideEmptyStateArea(subscriber) {
      if (!this.validateSubscriber(subscriber)) {
        return
      }
      this.showData = true
      this.showEmptyState = false
    },
    refreshButtonClicked() {
      this.isActive = true
      this.$emit("refreshButtonClicked")
    },
    widgetAction() {
      this.$emit("widgetAction", this.subscriber)
    },
    validateSubscriber(subscriber) {
      return this.subscriber === subscriber
    },
    checkDataLength(subscriber, dataLength) {
      if (!this.validateSubscriber(subscriber)) {
        return
      }
      if (dataLength === 0) {
        this.showData = false
        this.showEmptyState = true
      } else if (dataLength === null || dataLength === undefined) {
        this.showData = false
        this.showEmptyState = false
      } else {
        this.showData = true
        this.showEmptyState = false
      }
    },
    defaultCallback() {
      alert("default button click")
    },
    doSearch(data) {
      this.searching = true
      EventBus.$emit("searching", data)
    },
    showAllEntries() {
      this.searching = false
      this.searchTerm = ""
      EventBus.$emit("end_searching")
    },
    cancelSearching() {
      this.searching = false
      this.searchTerm = ""
    },
    setButtonColor() {
      if (this.buttonColor === undefined) {
        return "btn-primary"
      } else if (this.buttonColor === "green") {
        return "btn-success"
      } else if (this.buttonColor === "yellow") {
        return "btn-warning"
      } else if (this.buttonColor === "red") {
        return "btn-danger"
      } else if (this.buttonColor === "blue") {
        return "btn-info"
      }
    },
  },
  computed: {
    getEmptyStateLabel() {
      if (this.title === null || this.title === undefined) {
        return this.$tc("phrases.noData", 2)
      } else {
        return this.$tc("phrases.noDataFoundFor", 1, {
          data: this.title,
        })
      }
    },
    getEmptyStateButtonText() {
      if (this.title === null || this.title === undefined) {
        return this.$tc("phrases.createFirsRecord", 1)
      } else {
        return this.$tc("phrases.createFirsRecord", 2, {
          data: this.title,
        })
      }
    },
  },
  watch: {
    searchTerm: debounce(function () {
      if (this.searchTerm.length > 0) {
        this.doSearch(this.searchTerm)
      }
      if (this.searching && this.searchTerm.length == 0) {
        this.showAllEntries()
      }
    }, 1000),
  },
}
</script>

<style lang="scss" scoped>
.refresh-button {
  animation: rotate 1.4s ease 0.5s;
}

@keyframes rotate {
  0% {
    transform: rotate(360deg);
  }
}

.full-width-input-with-icon {
  width: calc(100% - 32px) !important;
}

.full-width-input-with-ending-icon {
  width: calc(100% - 70px) !important;
}

.full-width-input {
  width: calc(100%) !important;
}

.tabs {
  position: absolute;
  right: 1rem;
}

.nopadding {
  padding: 30px 0 0 0 !important;
}

.chic {
  margin-bottom: -10px !important;
  margin-left: -2px !important;
  margin-top: 0 !important;
  top: 16px !important;
  width: 98% !important;
  left: 1% !important;
  color: white !important;
  border-radius: 3px;
}

.chic-title {
  color: white;
  font-weight: 300;
  line-height: 22px;
  font-size: 1rem;
  margin-left: 5px;
  white-space: pre;
}

.chic-button {
  padding: 8px !important;
}

.chic-icon {
  color: white !important;
}

.md-toolbar[data-color="default"] {
  background: rgb(61, 59, 63);
  background: linear-gradient(
    162deg,
    rgba(61, 59, 63, 1) 0%,
    rgba(121, 117, 125, 1) 50%,
    rgba(101, 98, 105, 1) 100%
  );
  box-shadow:
    0 12px 20px -10px rgba(130, 130, 130, 0.28),
    0 4px 20px 0 rgba(26, 26, 26, 0.12),
    0 7px 8px -5px rgba(83, 80, 84, 0.2);

  h4 {
    color: #fefefe;
  }

  svg {
    color: #fefefe;
  }

  .chic-button {
    background-color: #0a0a0c !important;
    color: #fefefe !important;
  }
}

.md-toolbar[data-color="green"] {
  background: rgb(68, 113, 68);
  background: linear-gradient(
    162deg,
    rgba(68, 113, 68, 1) 0%,
    rgba(90, 149, 90, 1) 50%,
    rgba(102, 171, 102, 1) 100%
  );
  box-shadow:
    0 12px 20px -10px rgba(76, 175, 80, 0.28),
    0 4px 20px 0 rgba(0, 0, 0, 0.12),
    0 7px 8px -5px rgba(76, 175, 80, 0.2);

  h4 {
    color: #fefefe;
  }

  svg {
    color: #fefefe;
  }

  .chic-button {
    background-color: #325932 !important;
    color: #fefefe !important;
  }
}

.md-toolbar[data-color="orange"] {
  background: rgb(164, 106, 0);
  background: linear-gradient(
    162deg,
    rgba(164, 106, 0, 1) 0%,
    rgba(218, 142, 1, 1) 50%,
    rgba(255, 165, 0, 1) 100%
  );
  box-shadow:
    0 12px 20px -10px rgba(255, 165, 0, 0.28),
    0 4px 20px 0 rgba(255, 165, 0, 0.12),
    0 7px 8px -5px rgba(255, 165, 0, 0.2);

  h4 {
    color: #fefefe;
  }

  svg {
    color: #fefefe;
  }

  .chic-button {
    background-color: orangered !important;
    color: #fefefe !important;
  }
}

.md-toolbar[data-color="red"] {
  background: rgb(96, 28, 28);
  background: linear-gradient(
    162deg,
    rgba(96, 28, 28, 1) 0%,
    rgba(198, 73, 92, 1) 50%,
    rgba(236, 17, 50, 1) 100%
  );
  box-shadow:
    0 12px 20px -10px rgba(255, 0, 39, 0.28),
    0 4px 20px 0 rgba(255, 0, 39, 0.12),
    0 7px 8px -5px rgba(255, 0, 39, 0.2);

  h4 {
    color: #fefefe;
  }

  svg {
    color: #fefefe;
  }

  .chic-button {
    background-color: #a81e10 !important;
    color: #fefefe !important;
  }
}

.search-area {
  float: right;
  margin: auto;
  width: 80% !important;
}

.search-input {
  width: 80% !important;
  margin: auto;
}

.pointer {
  cursor: pointer;
}

.empty-state {
  width: 100%;
  height: 20%;
  margin: auto;
}

.loading-state {
  width: 30%;
  height: 30%;
  margin: auto;
}

.md-toolbar-section-start {
  width: 40%;
}

.md-toolbar-section-end {
  width: 60%;
}
</style>
