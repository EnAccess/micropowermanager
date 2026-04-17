<template>
  <div>
    <widget
      :id="'village-detail'"
      :title="village.name ? `${$tc('words.village')} - ${village.name}` : $tc('words.village')"
      :subscriber="subscriber"
      color="primary"
    >
      <md-card>
        <md-card-content>
          <form class="md-layout md-gutter" data-vv-scope="village-detail-form">
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has('village-detail-form.name'),
                }"
              >
                <label for="village_name">{{ $tc("words.name") }}</label>
                <md-input
                  id="village_name"
                  name="name"
                  v-model="village.name"
                  :disabled="!$can('settings')"
                  v-validate="'required|min:2'"
                />
                <span class="md-error">
                  {{ errors.first("village-detail-form.name") }}
                </span>
              </md-field>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has('village-detail-form.country'),
                }"
              >
                <label for="country_id">{{ $tc("words.country") }}</label>
                <md-select
                  id="country_id"
                  name="country"
                  v-model="village.countryId"
                  :disabled="!$can('settings')"
                  v-validate="'required'"
                >
                  <md-option
                    v-for="country in countryService.list"
                    :key="country.id"
                    :value="country.id"
                  >
                    {{ country.name || country.country_name }}
                  </md-option>
                </md-select>
                <span class="md-error">
                  {{ errors.first("village-detail-form.country") }}
                </span>
              </md-field>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has('village-detail-form.mini_grid'),
                }"
              >
                <label for="mini_grid_id">{{ $tc("words.miniGrid") }}</label>
                <md-select
                  id="mini_grid_id"
                  name="mini_grid"
                  v-model="village.miniGridId"
                  :disabled="!$can('settings')"
                  v-validate="'required'"
                >
                  <md-option
                    v-for="miniGrid in miniGridService.list"
                    :key="miniGrid.id"
                    :value="miniGrid.id"
                  >
                    {{ miniGrid.name }}
                  </md-option>
                </md-select>
                <span class="md-error">
                  {{ errors.first("village-detail-form.mini_grid") }}
                </span>
              </md-field>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field>
                <label for="village_location">{{ $tc("words.location") }}</label>
                <md-input
                  id="village_location"
                  :value="village.points || '-'"
                  disabled
                />
              </md-field>
            </div>
          </form>
        </md-card-content>

        <md-card-actions>
          <md-button
            v-if="$can('settings')"
            class="md-raised md-primary"
            :disabled="loading"
            @click="saveVillage"
          >
            {{ $tc("words.save") }}
          </md-button>
          <md-button class="md-raised" :disabled="loading" @click="goBack">
            {{ $tc("words.close") }}
          </md-button>
        </md-card-actions>
      </md-card>
    </widget>
    <md-progress-bar md-mode="indeterminate" v-if="loading" />
  </div>
</template>

<script>
import { notify } from "@/mixins/notify.js"
import { CityService } from "@/services/CityService.js"
import CountryService from "@/services/CountryService.js"
import { MiniGridService } from "@/services/MiniGridService.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "VillageDetail",
  mixins: [notify],
  components: { Widget },
  data() {
    return {
      subscriber: "village-detail",
      cityService: new CityService(),
      miniGridService: new MiniGridService(),
      countryService: new CountryService(),
      villageId: this.$route.params.id,
      loading: false,
      village: {
        id: null,
        name: "",
        countryId: null,
        miniGridId: null,
        points: "",
      },
    }
  },
  mounted() {
    this.loadVillage()
  },
  methods: {
    async loadVillage() {
      this.loading = true
      try {
        await Promise.all([
          this.miniGridService.getMiniGrids(),
          this.countryService.getCountries(),
        ])

        const city = await this.cityService.getCity(this.villageId, true)
        this.village = {
          id: city.id,
          name: city.name,
          countryId: city.country_id,
          miniGridId: city.mini_grid_id,
          points: city.location?.points || "",
        }

        EventBus.$emit("widgetContentLoaded", this.subscriber, 1)
      } catch (e) {
        EventBus.$emit("widgetContentLoaded", this.subscriber, 0)
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
    async saveVillage() {
      const validator = await this.$validator.validateAll("village-detail-form")
      if (!validator) {
        return
      }

      this.loading = true
      try {
        const payload = {
          name: this.village.name,
          miniGridId: this.village.miniGridId,
          countryId: this.village.countryId,
          // Backend validation currently requires points on update.
          points: this.village.points || "0,0",
        }

        const updatedVillage = await this.cityService.updateCity(
          this.village.id,
          payload,
        )

        this.village = {
          ...this.village,
          name: updatedVillage.name,
          countryId: updatedVillage.country_id,
          miniGridId: updatedVillage.mini_grid_id,
        }

        this.alertNotify(
          "success",
          this.$tc("messages.successfullyUpdated", 0, {
            item: this.$tc("words.village"),
          }),
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
    goBack() {
      this.$router.push("/villages")
    },
  },
  watch: {
    "$route.params.id": async function (newVillageId) {
      this.villageId = newVillageId
      await this.loadVillage()
    },
  },
}
</script>

<style scoped lang="scss"></style>
