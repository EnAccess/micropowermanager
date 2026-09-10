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
      v-if="optionsVisible && matches.length"
      class="city-options md-scrollbar"
      @mousedown.native.prevent
    >
      <md-list-item
        v-for="city in matches"
        :key="city.id"
        @click="onSelected(city)"
      >
        {{ city.name }}
      </md-list-item>
    </md-list>
  </div>
</template>

<script>
import { notify } from "@/mixins/notify.js"
import { CityService } from "@/services/CityService.js"

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
  },
  data() {
    return {
      cityService: new CityService(),
      searchTerm: "",
      optionsVisible: false,
    }
  },
  async mounted() {
    try {
      await this.cityService.getCities()
      this.searchTerm = this.cityName(this.value)
    } catch (e) {
      this.alertNotify("error", e.message)
    }
  },
  computed: {
    matches() {
      const term = this.searchTerm.trim().toLowerCase()
      if (!term) return this.cityService.list

      return this.cityService.list.filter((city) =>
        city.name.toLowerCase().includes(term),
      )
    },
  },
  watch: {
    value(cityId) {
      this.searchTerm = this.cityName(cityId)
    },
    searchTerm(term) {
      // The field's clear button empties the input directly, which has to reset
      // the selection as well.
      if (term === "") {
        this.$emit("input", null)
        this.$emit("select", null)
      }
    },
  },
  methods: {
    cityName(cityId) {
      const city = this.cityService.list.find((city) => city.id === cityId)
      return city ? city.name : ""
    },
    onSelected(city) {
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
</style>
