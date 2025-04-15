<template>
  <div>
    <widget
      :id="'client-addresses'"
      :title="$tc('words.address', 1)"
      :button="true"
      :button-text="$tc('phrases.newAddress')"
      color="green"
      @widgetAction="addNewAddress"
      :paginator="addresses.paginator"
      :subscriber="subscriber"
    >
      <md-table
        style="width: 100%"
        v-model="addresses.list"
        md-card
        md-fixed-header
      >
        <md-table-row
          @click="editAddress(item, index)"
          slot="md-table-row"
          slot-scope="{ item, index }"
        >
          <md-table-cell :md-label="$tc('words.street')" md-sort-by="street">
            {{ item.street }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.city')" md-sort-by="city">
            {{ item.city }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.phone')" md-sort-by="phone">
            {{ item.phone }}
          </md-table-cell>
          <md-table-cell :md-label="$tc('words.primary')" md-sort-by="primary">
            <input
              type="checkbox"
              readonly
              :checked="item.primary"
              onclick="return false;"
            />
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
    <md-dialog
      class="address-edit-container md-size-100"
      :md-active.sync="modalVisibility"
    >
      <md-dialog-title v-if="editFlag">
        {{ $tc("phrases.updateAddress") }}
      </md-dialog-title>
      <md-dialog-title v-if="!editFlag">
        {{ $tc("phrases.newAddress") }}
      </md-dialog-title>
      <md-dialog-content class="md-scrollbar">
        <div class="md-layout md-gutter">
          <div class="md-layout-item md-size-50 md-small-size-100">
            <md-field name="city">
              <label for="city">{{ $tc("words.city") }}</label>
              <md-select name="city" id="city" v-model="newAddress.city_id">
                <md-option
                  value="0"
                  disabled
                  v-if="!editFlag || newAddress.city_id === null"
                >
                  {{ $tc("words.city") }}
                </md-option>
                <md-option
                  v-for="city in cities"
                  :key="city.id"
                  :value="city.id"
                >
                  {{ city.name }}
                </md-option>
              </md-select>
            </md-field>
          </div>

          <div class="md-layout-item md-size-50 md-small-size-100">
            <md-field>
              <label for="Street">
                {{ $tc("words.street") }}
              </label>
              <md-input
                type="text"
                id="Street"
                name="Street"
                v-model="newAddress.street"
              />
            </md-field>
          </div>
        </div>

        <div class="md-layout md-gutter md-size-100">
          <div class="md-layout-item md-size-50 md-small-size-100">
            <md-field name="email">
              <label for="email">{{ $tc("words.email") }}</label>
              <md-input type="email" name="email" v-model="newAddress.email" />
            </md-field>
          </div>

          <div class="md-layout-item md-size-50 md-small-size-100">
            <template>
              <vue-tel-input
                name="phone"
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
                enabledCountryCode="true"
                v-model="newAddress.phone"
                @validate="validatePhone"
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
        </div>

        <div class="md-layout md-size-100">
          <div class="md-layout-item md-size-100">
            <label>
              <input type="checkbox" v-model="newAddress.primary" />
              {{ $tc("words.primary") }}
            </label>
          </div>
        </div>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button class="md-accent" @click="closeModal()">
          {{ $tc("words.close") }}
        </md-button>

        <md-button
          class="md-primary btn-lg"
          @click="saveAddress()"
          v-text="editFlag ? this.$tc('words.update') : this.$tc('words.save')"
        />
      </md-dialog-actions>
    </md-dialog>
  </div>
</template>

<script>
import { EventBus } from "@/shared/eventbus"
import { Address, Addresses } from "@/services/AddressService"
import Widget from "@/shared/Widget.vue"
import { CityService } from "@/services/CityService"

export default {
  name: "Addresses",
  components: { Widget },
  props: {
    personId: Number,
  },
  data() {
    return {
      cityService: new CityService(),
      addresses: new Addresses(this.personId),
      subscriber: "personAddresses",
      modalVisibility: false,
      newAddress: {},
      cities: [],
      editFlag: false,
      addressIndex: 0,
      phone: {
        valid: true,
      },
    }
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  destroyed() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.addresses.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.addresses.list.length,
      )
    },
    addNewAddress() {
      this.editFlag = false
      this.showModal()
    },
    showModal() {
      if (this.cities.length === 0) {
        this.getCities()
      }
      this.modalVisibility = true
    },
    editAddress(address, index) {
      this.editFlag = true
      this.addressIndex = index
      this.newAddress = {
        id: address.id,
        email: address.email,
        street: address.street,
        phone: address.phone,
        city_id: address.city_id,
        primary: address.primary,
      }
      this.showModal()
    },
    async getCities() {
      try {
        this.cities = await this.cityService.getCities()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    saveAddress() {
      if (this.validateNewAddress()) {
        this.modalVisibility = false

        if (this.editFlag) {
          this.addresses.updateAddress(this.newAddress).then((response) => {
            this.addresses.list = this.addresses.list.map(function (item) {
              if (response.data.data.is_primary === 1) {
                item.primary = false
              }
              if (item.id === response.data.data.id) {
                let updatedAddress = new Address()
                return updatedAddress.fromJson(response.data.data)
              }
              return item
            })
          })
        } else {
          this.addresses.newAddress(this.newAddress).then((response) => {
            this.addresses.appendList(response.data.data)
          })
        }
        this.newAddress = {}
      }
    },
    validatePhone(phone) {
      this.phone = phone
    },
    validateNewAddress() {
      if (!("city_id" in this.newAddress) || !this.newAddress.city_id) {
        this.$swal({
          type: "error",
          title: this.$tc("phrase.missingField"),
          text: "City is required",
        })
        return false
      } else if ("phone" in this.newAddress) {
        if (this.newAddress.phone.length === 0) {
          this.$swal({
            type: "error",
            title: this.$tc("phrase.missingField"),
            text: "Phone number is required",
          })
          return false
        }
        if (!this.newAddress.phone.startsWith("+")) {
          if (this.newAddress.phone.startsWith("00")) {
            this.newAddress.phone = this.newAddress.phone.replace("00", "+")
          } else {
            this.$swal({
              type: "error",
              title: this.$tc("phrase.missingField"),
              text: "Phone format is, +CountryCode Number (+255123123123) ",
            })
            return false
          }
        }
      }
      return true
    },
    closeModal() {
      this.modalVisibility = false
      this.newAddress = {}
    },
  },
}
</script>

<style lang="css">
.address-edit-container {
  padding: 1rem;
}
.md-dialog-container {
  min-width: 240px !important;
}
</style>
