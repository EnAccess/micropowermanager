<template>
  <div>
    <new-user
      @newUserClosed="showNewUser = false"
      :showNewUser="showNewUser"
      :user="userService.user"
      @createUser="createUser"
    ></new-user>
    <edit-user
      @editUserClosed="showEditUser = false"
      :showEditUser="showEditUser"
      :user="userService.user"
      @updateUser="updateUser"
      :cities="cities"
    />
    <widget
      :title="$tc('phrases.userManagement')"
      :button-text="$tc('phrases.newUser')"
      :button="true"
      @widgetAction="showNewUser = true"
      :subscriber="subscriber"
      :paginator="userService.paginator"
      :key="resetKey"
    >
      <div class="md-layout">
        <div class="md-layout md-gutter">
          <div
            class="md-layout-item md-size-100 md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100"
          >
            <md-table md-card style="margin-left: 0">
              <md-table-row>
                <md-table-head>
                  {{ $tc("words.id") }}
                </md-table-head>
                <md-table-head>
                  {{ $tc("words.name") }}
                </md-table-head>
                <md-table-head>
                  {{ $tc("words.email") }}
                </md-table-head>
                <md-table-head>
                  {{ $tc("words.phone") }}
                </md-table-head>
              </md-table-row>

              <md-table-row
                @click="userDetail(user)"
                v-for="user in userService.users"
                :key="user.id"
                style="cursor: pointer"
              >
                <md-table-cell>{{ user.id }}</md-table-cell>
                <md-table-cell>{{ user.name }}</md-table-cell>
                <md-table-cell>{{ user.email }}</md-table-cell>
                <md-table-cell>{{ user.phone }}</md-table-cell>
              </md-table-row>
            </md-table>
            <md-button
              class="md-primary change-button-protected-pages-password"
              @click="protectedPageModalVisibility = true"
            >
              Change Protected Page Password
            </md-button>
          </div>
        </div>
        <md-dialog :md-active.sync="protectedPageModalVisibility">
          <md-dialog-title>Change Protected Page Password</md-dialog-title>
          <md-dialog-content>
            <div class="edit-container-protected-pages-password">
              <form class="md-layout">
                <md-field
                  :class="{
                    'md-invalid': errors.has('protectedPagePassword'),
                  }"
                >
                  <label for="protectedPagePassword">
                    New Protected Page Password
                  </label>
                  <md-input
                    type="password"
                    name="protectedPagePassword"
                    id="protectedPagePassword"
                    v-validate="'required|min:5'"
                    v-model="protectedPagePassword"
                    ref="protectedPagePasswordRef"
                  />
                  <span class="md-error">
                    {{ errors.first("protectedPagePassword") }}
                  </span>
                </md-field>
                <md-field
                  :class="{
                    'md-invalid': errors.has('confirmProtectedPagePassword'),
                  }"
                >
                  <label for="confirmProtectedPagePassword">
                    Confirm Protected Page Password
                  </label>
                  <md-input
                    type="password"
                    name="confirmProtectedPagePassword"
                    id="confirmProtectedPagePassword"
                    v-model="confirmProtectedPagePassword"
                    v-validate="
                      'required|confirmed:protectedPagePasswordRef|min:5'
                    "
                  />
                  <span class="md-error">
                    {{ errors.first("confirmProtectedPagePassword") }}
                  </span>
                </md-field>
                <md-progress-bar
                  md-mode="indeterminate"
                  v-if="sendingProtectedPage"
                />
              </form>
            </div>
          </md-dialog-content>
          <md-dialog-actions>
            <md-button
              class="md-raised md-primary"
              @click="changeProtectedPagePassword"
            >
              {{ $tc("words.save") }}
            </md-button>
            <md-button @click="protectedPageModalVisibility = false">
              {{ $tc("words.close") }}
            </md-button>
          </md-dialog-actions>
        </md-dialog>
      </div>
    </widget>
    <md-progress-bar md-mode="indeterminate" v-if="sending" />
  </div>
</template>
<script>
import Widget from "@/shared/Widget.vue"
import { EventBus } from "@/shared/eventbus"
import { UserService } from "@/services/UserService"
import { CityService } from "@/services/CityService"
import NewUser from "./NewUser"
import EditUser from "./EditUser"
import { notify } from "@/mixins/notify"
import { MainSettingsService } from "@/services/MainSettingsService"

export default {
  name: "ProfileManagement",
  mixins: [notify],
  components: { Widget, NewUser, EditUser },
  data() {
    return {
      subscriber: "user-management",
      sending: false,
      showEditUser: false,
      selectedCity: 0,
      userService: new UserService(),
      cityService: new CityService(),
      userId: 0,
      showNewUser: false,
      resetKey: 1,
      cities: [],
      protectedPageModalVisibility: false,
      protectedPagePassword: "",
      confirmProtectedPagePassword: "",
      sendingProtectedPage: false,
      mainSettingsService: new MainSettingsService(),
      firstStepClicked: false,
    }
  },
  created() {
    this.getCities()
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("newUserCreated", () => this.resetKey++)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded")
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.userService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.userService.users.length,
      )
    },
    async getCities() {
      try {
        await this.cityService.getCities()
        this.cities = this.cityService.cities
      } catch (error) {
        this.alertNotify("error", error.message)
      }
    },
    async userDetail(user) {
      try {
        await this.userService.get(user.id)
        this.showEditUser = true
      } catch (error) {
        this.alertNotify("error", error)
      }
    },
    async updateUser(user) {
      this.sending = true
      if (user.cityId !== 0) {
        this.userService.user.cityId = user.cityId
      }
      try {
        await this.userService.update()
        this.alertNotify("success", this.$tc("words.profile", 2))
        this.showEditUser = false
        this.resetKey++
      } catch (error) {
        this.alertNotify("error", error)
      }
      this.sending = false
    },
    async createUser() {
      this.sending = true
      try {
        await this.userService.create()
        this.alertNotify("success", this.$tc("phrases.newUser", 2))
        this.showNewUser = false
        this.resetKey++
      } catch (error) {
        this.alertNotify("error", error.message)
      }
      this.sending = false
    },
    async changeProtectedPagePassword() {
      this.sendingProtectedPage = true
      let validation = await this.$validator.validateAll()
      if (!validation) {
        this.sendingProtectedPage = false
        return
      }
      try {
        await this.mainSettingsService.list()
        this.mainSettingsService.mainSettings.protectedPagePassword =
          this.protectedPagePassword
        await this.mainSettingsService.update()
        this.alertNotify(
          "success",
          "Protected page password updated successfully",
        )
        this.protectedPageModalVisibility = false
        this.protectedPagePassword = ""
        this.confirmProtectedPagePassword = ""
        // Clear validation errors
        this.$validator.reset()
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.sendingProtectedPage = false
    },
  },
}
</script>

<style scoped>
.change-button-protected-pages-password {
  background-color: #4f4e94 !important;
  color: #fefefe !important;
  float: right;
}
.edit-container-protected-pages-password {
  padding: 1rem;
}
</style>
