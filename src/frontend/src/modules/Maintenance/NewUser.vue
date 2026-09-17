<template>
  <div>
    <widget
      v-if="newUser"
      :title="$tc('phrases.newMaintenanceUser')"
      color="secondary"
    >
      <div>
        <form @submit.prevent="submitNewUserForm">
          <md-card>
            <md-card-content>
              <div class="md-layout md-gutter">
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.name')),
                    }"
                  >
                    <label for="name">
                      {{ $tc("words.name") }}
                    </label>

                    <md-input
                      type="text"
                      :name="$tc('words.name')"
                      id="name"
                      v-model="maintenanceService.personData.name"
                      :placeholder="$tc('words.name')"
                      v-validate="'required|min:3'"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.name")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.surname')),
                    }"
                  >
                    <label for="surname">
                      {{ $tc("words.surname") }}
                    </label>

                    <md-input
                      type="text"
                      v-validate="'required'"
                      v-model="maintenanceService.personData.surname"
                      id="surname"
                      :name="$tc('words.surname')"
                      :placeholder="$tc('words.surname')"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.surname")) }}
                    </span>
                  </md-field>
                </div>

                <div class="md-layout-item md-size-50 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.miniGrid')),
                    }"
                  >
                    <label for="mini-grids" class="control-label">
                      {{ $tc("words.miniGrid") }}
                    </label>

                    <md-select
                      v-validate="'required'"
                      id="mini-grids"
                      :name="$tc('words.miniGrid')"
                      v-model="maintenanceService.personData.mini_grid_id"
                    >
                      <md-option value selected disabled>
                        &#45;&#45;
                        {{ $tc("words.select") }}
                        &#45;&#45;
                      </md-option>
                      <md-option
                        v-for="(miniGrid, index) in miniGrids"
                        :value="miniGrid.id"
                        :key="index"
                      >
                        {{ miniGrid.name }}
                      </md-option>
                    </md-select>
                    <span class="md-error">
                      {{ errors.first($tc("words.miniGrid")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <template>
                    <vue-tel-input
                      id="phone"
                      :validCharactersOnly="true"
                      mode="international"
                      invalidMsg="invalid phone number"
                      :disabledFetchingCountry="false"
                      :disabledFormatting="false"
                      placeholder="Enter a phone number"
                      :required="true"
                      :preferredCountries="['TZ', 'CM', 'KE', 'NG', 'UG']"
                      autocomplete="off"
                      :name="$tc('words.phone')"
                      enabledCountryCode="true"
                      v-model="maintenanceService.personData.phone"
                      @validate="validatePhone"
                      @input="onPhoneInput"
                    ></vue-tel-input>
                    <span
                      v-if="!phone.valid && firstStepClicked"
                      style="color: red"
                      class="md-error"
                    >
                      invalid phone number
                    </span>
                  </template>
                </div>
                <div class="md-layout-item md-size-50 md-small-size-100">
                  <city-autocomplete
                    v-model="maintenanceService.personData.city_id"
                    :name="$tc('words.city')"
                    :label="$tc('phrases.livingIn')"
                    required
                    :error="errors.first($tc('words.city'))"
                  />
                </div>
              </div>
              <md-progress-bar md-mode="indeterminate" v-if="loading" />
            </md-card-content>
            <md-card-actions>
              <md-button class="md-raised" @click="onClose()">
                {{ $tc("words.close") }}
              </md-button>
              <md-button
                class="md-raised md-primary"
                :disabled="loading"
                type="submit"
              >
                {{ $tc("words.save") }}
              </md-button>
            </md-card-actions>
          </md-card>
        </form>
      </div>
    </widget>
    <redirection-modal
      :redirection-url="redirectionUrl"
      :imperative-item="imperativeItem"
      :dialog-active="redirectDialogActive"
    />
  </div>
</template>

<script>
import RedirectionModal from "../../shared/RedirectionModal.vue"

import { notify } from "@/mixins/notify.js"
import { MaintenanceService } from "@/services/MaintenanceService.js"
import { MiniGridService } from "@/services/MiniGridService.js"
import CityAutocomplete from "@/shared/CityAutocomplete.vue"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "NewUser",
  mixins: [notify],
  components: { CityAutocomplete, Widget, RedirectionModal },
  props: {
    newUser: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      miniGrids: [],
      miniGridService: new MiniGridService(),
      maintenanceService: new MaintenanceService(),
      loading: false,
      imperativeItem: "Mini-Grid",
      redirectDialogActive: false,
      redirectionUrl: "/locations/add-mini-grid",
      phone: {
        valid: true,
      },
    }
  },

  mounted() {
    EventBus.$on("getLists", () => {
      this.getMiniGrids()
    })
  },
  methods: {
    async getMiniGrids() {
      try {
        this.miniGrids = await this.miniGridService.getMiniGrids()
        if (this.miniGrids.length === 0) {
          this.redirectDialogActive = true
        }
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    validatePhone(phone) {
      this.phone = phone
    },
    onPhoneInput(_, phone) {
      this.phone = phone
    },
    async submitNewUserForm() {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      try {
        this.loading = true
        await this.maintenanceService.createMaintenance(
          this.maintenanceService.personData,
        )
        this.loading = false
        this.alertNotify("success", this.$tc("phrases.newMaintenanceUser", 2))
        this.maintenanceService.resetPersonData()
        this.onClose()
      } catch (e) {
        this.loading = false
        this.alertNotify("error", e.message)
      }
    },
    onClose() {
      EventBus.$emit("newUserClosed", false)
    },
  },
}
</script>

<style scoped lang="scss">
.full-width {
  width: 100% !important;
}
</style>
