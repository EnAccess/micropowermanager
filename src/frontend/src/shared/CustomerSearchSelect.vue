<template>
  <md-field :class="{ 'md-invalid': !!error }">
    <label>{{ $tc("words.customer") }}</label>
    <md-select
      :value="value"
      @input="$emit('input', $event)"
      @md-opened="focusSearchInput"
      @md-closed="searchTerm = ''"
    >
      <div class="select-search-row" @click.stop @mousedown.stop>
        <md-field md-inline>
          <md-icon>search</md-icon>
          <md-input
            ref="searchInput"
            v-model="searchTerm"
            :placeholder="$tc('phrases.searchCustomer')"
            @click.native.stop
            @mousedown.native.stop
            @keydown.native.stop
          />
        </md-field>
      </div>
      <md-option disabled v-if="isSearching">Searching…</md-option>
      <md-option disabled v-else-if="!selectOptions.length">
        {{ searchHint }}
      </md-option>
      <md-option
        v-else
        v-for="customer in selectOptions"
        :key="customer.id"
        :value="customer.id"
      >
        {{ customer.name }}
      </md-option>
    </md-select>
    <span class="md-error">{{ error }}</span>
  </md-field>
</template>

<script>
import { notify } from "@/mixins/notify.js"
import {
  MINIMUM_CUSTOMER_SEARCH_LENGTH,
  PersonService,
} from "@/services/PersonService.js"

const debounce = require("debounce")

export default {
  name: "CustomerSearchSelect",
  mixins: [notify],
  props: {
    value: {
      default: null,
    },
    error: {
      type: String,
      default: null,
    },
  },
  data() {
    return {
      personService: new PersonService(),
      options: [],
      searchTerm: "",
      isSearching: false,
      selectedCustomer: null,
    }
  },
  computed: {
    // a later search must not drop the already picked customer out of the
    // option list, or md-select renders the field as if nothing were selected
    selectOptions() {
      if (!this.selectedCustomer) return this.options
      const isInResults = this.options.some(
        (customer) => customer.id === this.selectedCustomer.id,
      )
      return isInResults
        ? this.options
        : [this.selectedCustomer, ...this.options]
    },
    searchHint() {
      return this.searchTerm.length < MINIMUM_CUSTOMER_SEARCH_LENGTH
        ? this.$tc("phrases.searchCustomer")
        : `No customer matching "${this.searchTerm}" was found.`
    },
  },
  watch: {
    searchTerm: debounce(function () {
      this.search(this.searchTerm.trim())
    }, 400),
    value(id) {
      if (id === null) {
        this.options = []
        this.selectedCustomer = null
        return
      }
      const selected = this.options.find((customer) => customer.id === id)
      if (selected) this.selectedCustomer = selected
    },
  },
  methods: {
    async search(term) {
      this.isSearching = true
      try {
        this.options = await this.personService.searchCustomerOptions(term)
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.isSearching = false
      }
    },
    focusSearchInput() {
      this.$nextTick(() => {
        const input = this.$refs.searchInput
        if (input && typeof input.focus === "function") input.focus()
      })
    },
  },
}
</script>

<style scoped lang="scss">
.select-search-row {
  position: sticky;
  top: 0;
  z-index: 1;
  padding: 0 1rem;
  background: #fff;
  border-bottom: 1px solid #e0e0e0;
}
</style>
