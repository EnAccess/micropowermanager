<template>
  <div class="city-autocomplete">
    <md-field md-clearable :class="{ 'md-invalid': error !== null }">
      <label :for="name">{{ label || $tc("words.city") }}</label>
      <md-input
        :id="name"
        :name="name"
        v-model="searchTerm"
        v-validate="required ? 'required' : ''"
        autocomplete="off"
        @focus="optionsVisible = true"
        @blur="optionsVisible = false"
      />
      <span class="md-error">{{ error }}</span>
    </md-field>
    <md-list
      v-if="optionsVisible"
      class="city-options md-scrollbar"
      @mousedown.native.prevent
    >
      <md-list-item
        v-for="city in options"
        :key="city.id"
        @click="onSelected(city)"
      >
        {{ city.name }}
      </md-list-item>
      <md-list-item v-if="!options.length && !loading" disabled>
        {{ $tc("phrases.noRecords") }}
      </md-list-item>
      <md-list-item
        v-if="page < lastPage"
        :disabled="appending"
        @click="loadMore"
      >
        <span class="load-more">
          {{ $tc("phrases.loadMore") }}{{ appending ? "…" : "" }}
        </span>
      </md-list-item>
    </md-list>
    <md-progress-bar md-mode="indeterminate" v-if="loading && !appending" />
  </div>
</template>

<script>
import { notify } from "@/mixins/notify.js"
import { CityService } from "@/services/CityService.js"

const debounce = require("debounce")

export default {
  name: "CityAutocomplete",
  mixins: [notify],
  // Share the parent's validator so the field takes part in the surrounding
  // form's validation scope instead of validating on its own.
  inject: {
    $validator: "$validator",
  },
  props: {
    value: {
      type: Number,
      default: null,
    },
    label: {
      type: String,
      default: null,
    },
    name: {
      type: String,
      default: "city",
    },
    required: {
      type: Boolean,
      default: false,
    },
    error: {
      type: String,
      default: null,
    },
    exclude: {
      type: Number,
      default: null,
    },
  },
  data() {
    return {
      cityService: new CityService(),
      cities: [],
      searchTerm: "",
      selectedId: null,
      selectedName: "",
      page: 1,
      lastPage: 1,
      loadingPage: null,
      optionsVisible: false,
    }
  },
  async mounted() {
    await this.loadFirstPage()
    await this.showSelected(this.value)
  },
  computed: {
    options() {
      return this.cities.filter((city) => city.id !== this.exclude)
    },
    loading() {
      return this.loadingPage !== null
    },
    appending() {
      return this.loadingPage > 1
    },
  },
  watch: {
    value(cityId) {
      this.showSelected(cityId)
    },
    searchTerm: debounce(function (term) {
      if (term === this.selectedName) return

      // The field's clear button empties the input directly, which has to reset
      // the selection as well.
      if (term === "") {
        this.selectedId = null
        this.selectedName = ""
        this.$emit("input", null)
        this.$emit("select", null)
      }

      this.loadFirstPage()
    }, 300),
  },
  methods: {
    async loadFirstPage() {
      this.page = 1
      await this.loadPage(1)
    },
    async loadMore() {
      await this.loadPage(this.page + 1)
    },
    async loadPage(page) {
      this.loadingPage = page
      try {
        const { cities, lastPage } = await this.cityService.getCities({
          page,
          term: this.searchTerm,
        })
        this.cities = page === 1 ? cities : [...this.cities, ...cities]
        this.page = page
        this.lastPage = lastPage
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loadingPage = null
    },

    async showSelected(cityId) {
      if (cityId === this.selectedId) return

      if (!cityId) {
        this.selectedId = null
        this.selectedName = ""
        this.searchTerm = ""
        return
      }

      try {
        const city = await this.cityService.getCity(cityId)
        this.selectedId = city.id
        this.selectedName = city.name
        this.searchTerm = city.name
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    onSelected(city) {
      this.selectedId = city.id
      this.selectedName = city.name
      this.searchTerm = city.name
      this.optionsVisible = false
      this.$emit("input", city.id)
      this.$emit("select", city)
    },
  },
}
</script>

<style lang="scss" scoped>
.city-options {
  max-height: 12rem;
  overflow-y: auto;
  padding: 0;
  border: solid 1px #dedede;
  border-radius: 2px;
}

.load-more {
  font-weight: 500;
}
</style>
