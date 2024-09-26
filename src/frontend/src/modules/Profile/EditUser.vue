<template>
  <div>
    <widget v-if="showEditUser" :title="$tc('words.edit')" color="green">
      <form data-vv-scope="Edit-Form">
        <div class="edit-container">
          <md-card>
            <md-card-content class="md-layout md-gutter">
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Edit-Form.' + $tc('words.name')),
                  }"
                >
                  <label>{{ $tc("words.name") }}</label>
                  <md-input
                    disabled
                    v-model="user.name"
                    v-validate="'required|min:2|max:20'"
                    :name="$tc('words.name')"
                    id="name"
                  />
                  <md-icon>create</md-icon>
                  <span class="md-error">
                    {{ errors.first("Edit-Form." + $tc("words.name")) }}
                  </span>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label>{{ $tc("words.phone") }}</label>
                  <md-input
                    type="number"
                    name="phone"
                    id="phone"
                    v-model="user.phone"
                  />
                  <md-icon>phone</md-icon>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field>
                  <label>{{ $tc("words.street") }}</label>
                  <md-input v-model="user.street" name="street" id="street" />
                  <md-icon>contacts</md-icon>
                </md-field>
              </div>
              <div class="md-layout-item md-size-50 md-small-size-100">
                <md-field
                  :class="{
                    'md-invalid': errors.has('Edit-Form.' + $tc('words.city')),
                  }"
                >
                  <label for="city">
                    {{ $tc("words.city") }}
                  </label>
                  <md-select
                    v-model="selectedCity"
                    :name="$tc('words.city')"
                    id="city"
                    v-validate="'required'"
                  >
                    <md-option v-for="c in cities" :key="c.id" :value="c.id">
                      {{ c.name }}
                    </md-option>
                  </md-select>
                  <span class="md-error">
                    {{ errors.first("Edit-Form." + $tc("words.city")) }}
                  </span>
                </md-field>
              </div>
            </md-card-content>
            <md-card-actions>
              <md-button class="md-raised md-primary" @click="updateUser()">
                {{ $tc("words.save") }}
              </md-button>
              <md-button class="md-raised" @click="closeEditUser()">
                {{ $tc("words.close") }}
              </md-button>
            </md-card-actions>
          </md-card>
        </div>
      </form>
    </widget>
  </div>
</template>

<script>
import Widget from "../../shared/widget"
export default {
  components: { Widget },
  name: "EditUser",
  props: {
    showEditUser: {
      type: Boolean,
      default: false,
    },
    user: {
      type: Object,
      required: true,
    },
    cities: {
      type: Array,
      required: true,
    },
  },
  data() {
    return {
      sending: false,
      selectedCity: 0,
    }
  },
  mounted() {
    this.setSelectedCity()
  },
  methods: {
    async updateUser() {
      const validation = await this.$validator.validateAll("Edit-Form")
      if (!validation) {
        return
      }
      this.user.cityId = this.selectedCity
      this.$emit("updateUser", this.user)
    },
    setSelectedCity() {
      if (this.user.cityId) {
        this.selectedCity = this.cities
          .filter((x) => x.id === this.user.cityId)
          .map((x) => x.id)[0]
      }
    },
    closeEditUser() {
      this.$emit("editUserClosed")
    },
    alertNotify(type, message) {
      this.$notify({
        group: "notify",
        type: type,
        title: type + " !",
        text: message,
      })
    },
  },
  watch: {
    showEditUser() {
      this.setSelectedCity()
    },
  },
}
</script>

<style lang="scss" scoped>
.md-select-menu-container {
  z-index: 99999 !important;
}
</style>
