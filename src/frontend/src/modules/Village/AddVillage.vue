<template>
  <div>
    <widget :title="$tc('phrases.newVillage')" color="primary">
      <md-card class="md-layout-item md-size-100">
        <md-card-content>
          <div class="md-layout md-gutter md-size-100">
            <div class="md-layout-item md-size-70 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.name')),
                }"
              >
                <label for="city_name">
                  {{ $tc("words.name") }}
                </label>
                <md-input
                  id="cityName"
                  :name="$tc('words.name')"
                  v-model="cityName"
                  v-validate="'required|min:3'"
                />
                <span class="md-error">
                  {{ errors.first($tc("words.name")) }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-30 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has($tc('words.miniGrid')),
                }"
              >
                <label for="miniGrid">
                  {{ $tc("words.miniGrid") }}
                </label>
                <md-select
                  v-model="selectedMiniGridId"
                  name="miniGrid"
                  id="miniGrid"
                  v-validate="'required'"
                >
                  <md-option
                    v-for="mg in miniGridService.list"
                    :value="mg.id"
                    :key="mg.id"
                  >
                    {{ mg.name }}
                  </md-option>
                </md-select>
                <span class="md-error">
                  {{ errors.first($tc("words.miniGrid")) }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-30 md-small-size-100">
              <md-field
                :class="{ 'md-invalid': errors.has($tc('words.country')) }"
              >
                <label for="country">Country</label>
                <md-select
                  v-model="selectedCountryId"
                  name="country"
                  id="country"
                  v-validate="'required'"
                >
                  <md-option value selected disabled>
                    -- {{ $tc("words.select") }} --
                  </md-option>
                  <md-option
                    v-for="country in countries"
                    :value="country.id"
                    :key="country.id"
                  >
                    {{ country.country_name }}
                  </md-option>
                </md-select>
                <span class="md-error">
                  {{ errors.first($tc("words.country")) }}
                </span>
              </md-field>
            </div>
          </div>

          <div class="md-layout md-gutter md-size-100">
            <div
              class="md-layout md-gutter md-size-60 md-small-size-100"
              style="padding-left: 1.5rem !important"
            >
              <form
                class="md-layout md-gutter"
                @submit.prevent="setPoints"
                style="padding-left: 1.5rem !important"
              >
                <div class="md-layout-item md-size-30 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.latitude')),
                    }"
                  >
                    <label for="latitude">
                      {{ $tc("words.latitude") }}
                    </label>
                    <md-input
                      id="latitude"
                      :name="$tc('words.latitude')"
                      v-model="cityLatLng.lat"
                      step="any"
                      maxlength="8"
                      v-validate="'required|decimal:6'"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.latitude")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-30 md-small-size-100">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.longitude')),
                    }"
                  >
                    <label for="longitude">
                      {{ $tc("words.longitude") }}
                    </label>
                    <md-input
                      id="longitude"
                      :name="$tc('words.longitude')"
                      v-model="cityLatLng.lon"
                      step="any"
                      maxlength="8"
                      v-validate="'required|decimal:6'"
                    />
                    <span class="md-error">
                      {{ errors.first($tc("words.longitude")) }}
                    </span>
                  </md-field>
                </div>
                <div class="md-layout-item md-size-40 md-small-size-100">
                  <md-button type="submit" class="md-primary set-button">
                    {{ $tc("phrases.setPoints") }}
                  </md-button>
                </div>
              </form>
            </div>

            <div class="md-layout-item md-size-40 md-small-size-100">
              <md-button class="md-primary save-button" @click="saveVillage">
                {{ $tc("words.save") }}
              </md-button>
            </div>
          </div>

          <div class="md-layout-item md-size-100 map-area">
            <VillageMap
              ref="villageMapRef"
              :mapping-service="mappingService"
              :marker="true"
              @locationSet="villageLocationSet"
            />
          </div>
        </md-card-content>
        <md-progress-bar
          md-mode="indeterminate"
          class="md-progress-bar"
          v-if="loading"
        />
      </md-card>
    </widget>
    <redirection-modal
      :redirection-url="redirectionUrl"
      :imperative-item="imperativeItem"
      :dialog-active="redirectDialogActive"
    />
  </div>
</template>

<script>
import { latLonToGeoJsonPoint } from "@/Helpers/Utils.js"
import { notify } from "@/mixins/notify.js"
import { villageMapContext } from "@/mixins/villageMapContext.js"
import VillageMap from "@/modules/Map/VillageMap.vue"
import { CityService } from "@/services/CityService.js"
import { ClusterService } from "@/services/ClusterService.js"
import CountryService from "@/services/CountryService.js"
import { ICONS, MappingService } from "@/services/MappingService.js"
import { MiniGridService } from "@/services/MiniGridService.js"
import RedirectionModal from "@/shared/RedirectionModal.vue"
import Widget from "@/shared/Widget.vue"

export default {
  name: "AddVillage",
  mixins: [notify, villageMapContext],
  components: {
    VillageMap,
    Widget,
    RedirectionModal,
  },
  data() {
    return {
      clusterService: new ClusterService(),
      miniGridService: new MiniGridService(),
      mappingService: new MappingService(),
      redirectedMiniGridId: null,
      selectedMiniGridId: null,
      geoData: null,
      villageSaved: false,
      loading: false,
      lastVillage: null,
      cityName: null,
      cityIndex: 0,
      cityService: new CityService(),
      countryService: new CountryService(),
      countries: [],
      selectedCountryId: null,
      redirectionUrl: "/locations/add-mini-grid",
      imperativeItem: "Mini-Grid",
      redirectDialogActive: false,
    }
  },
  created() {
    this.redirectedMiniGridId = this.$route.params.id
    this.mappingService.setConstantMarkerUrl(ICONS.MINI_GRID)
    this.mappingService.setMarkerUrl(ICONS.VILLAGE)
    this.getCountries()
  },
  mounted() {
    this.setMiniGridOfVillage()
  },

  methods: {
    async setMiniGridOfVillage() {
      try {
        if (this.redirectedMiniGridId) {
          this.selectedMiniGridId = this.redirectedMiniGridId
          return
        }
        await this.getMiniGrids()

        if (this.miniGridService.list.length) {
          const selectedMiniGrid =
            this.miniGridService.list[this.miniGridService.list.length - 1]
          this.selectedMiniGridId = selectedMiniGrid.id
        } else {
          this.redirectDialogActive = true
        }
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getMiniGrids() {
      try {
        await this.miniGridService.getMiniGrids()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getCountries() {
      try {
        await this.countryService.getCountries()
        this.countries = this.countryService.list
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async saveVillage() {
      const validator = await this.$validator.validateAll()
      if (validator) {
        if (!this.selectedCountryId) {
          this.alertNotify("error", this.$tc("phrases.selectCountry"))
          return
        }
        try {
          this.loading = true
          const city = {
            name: this.cityName,
            miniGridId: this.selectedMiniGridId,
            countryId: this.selectedCountryId,
            geoJson: latLonToGeoJsonPoint(
              this.cityLatLng.lat,
              this.cityLatLng.lon,
            ),
          }
          await this.cityService.createCity(city)
          this.alertNotify("success", this.$tc("phrases.newVillageNotify", 1))
          this.loading = false
          await this.$router.replace(
            `/dashboards/mini-grid/${this.selectedMiniGridId}`,
          )
        } catch (e) {
          this.loading = false
          this.alertNotify("error", e.message)
        }
      }
    },
  },
  watch: {
    async selectedMiniGridId() {
      try {
        const miniGridWithGeoData = await this.loadVillageMapContext(
          this.selectedMiniGridId,
        )
        if (!miniGridWithGeoData) return
        this.$refs.villageMapRef.drawCluster()
        this.$refs.villageMapRef.setMiniGridMarker()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style lang="scss" scoped>
.md-progress-bar {
  position: absolute;
  top: 0;
  right: 0;
  left: 0;
}

.save-button {
  background-color: #325932 !important;
  color: #fefefe !important;
  top: 0.5rem;
  float: right;
}

.set-button {
  background-color: #448aff !important;
  color: #fefefe !important;
  top: 0.5rem;
  float: left;
}

.map-area {
  z-index: 1 !important;
}
</style>
