<template>
  <widget v-if="showNewUser" :title="$tc('phrases.newUser')" color="red">
    <form @submit.prevent="createUser" data-vv-scope="Create-Form">
      <div class="edit-container">
        <md-card>
          <md-card-content class="md-layout md-gutter">
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has('Create-Form.' + $tc('words.name')),
                }"
              >
                <label>{{ $tc("words.name") }}</label>
                <md-input
                  v-model="user.name"
                  v-validate="'required|min:2|max:20'"
                  :name="$tc('words.name')"
                  id="name"
                />
                <md-icon>create</md-icon>
                <span class="md-error">
                  {{ errors.first("Create-Form." + $tc("words.name")) }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has('Create-Form.' + $tc('words.email')),
                }"
              >
                <label>{{ $tc("words.email") }}</label>
                <md-input
                  type="text"
                  :name="$tc('words.email')"
                  id="email"
                  v-model="user.email"
                  v-validate="'required|email'"
                />
                <md-icon>email</md-icon>
                <span class="md-error">
                  {{ errors.first("Create-Form." + $tc("words.email")) }}
                </span>
              </md-field>
            </div>

            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has(
                    'Create-Form.' + $tc('words.password'),
                  ),
                }"
              >
                <label for="password">
                  {{ $tc("words.password") }}
                </label>
                <md-input
                  type="password"
                  :name="$tc('words.password')"
                  id="password"
                  v-validate="'required|min:3|max:15'"
                  v-model="user.password"
                  ref="passwordRef"
                />

                <span class="md-error">
                  {{ errors.first("Create-Form." + $tc("words.password")) }}
                </span>
              </md-field>
            </div>
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field
                :class="{
                  'md-invalid': errors.has(
                    'Create-Form.' + $tc('phrases.confirmPassword'),
                  ),
                }"
              >
                <label for="confirmPassword">
                  {{ $tc("phrases.confirmPassword") }}
                </label>
                <md-input
                  type="password"
                  :name="$tc('phrases.confirmPassword')"
                  id="confirmPassword"
                  v-model="user.confirmPassword"
                  v-validate="'required|min:3|max:15'"
                />
                <span class="md-error">
                  {{
                    errors.first(
                      "Create-Form." + $tc("phrases.confirmPassword"),
                    )
                  }}
                </span>
              </md-field>
            </div>
          </md-card-content>
          <md-card-content
            class="md-layout md-gutter"
            v-if="
              $store.getters['auth/getPermissions'].includes('roles.manage')
            "
          >
            <div class="md-layout-item md-size-50 md-small-size-100">
              <md-field>
                <label for="roles">
                  Roles
                  <span style="color: red">*</span>
                </label>
                <md-select id="roles" v-model="selectedRoles" multiple>
                  <md-option
                    v-for="r in roleService.roles"
                    :key="r.name"
                    :value="r.name"
                  >
                    {{ r.name }}
                  </md-option>
                </md-select>
                <span class="md-helper-text">
                  At least one role is required
                </span>
              </md-field>
            </div>
          </md-card-content>
          <md-card-actions>
            <md-button type="submit" class="md-raised md-primary">
              {{ $tc("words.create") }}
            </md-button>

            <md-button class="md-raised" @click="closeNewUser()">
              {{ $tc("words.close") }}
            </md-button>
          </md-card-actions>
        </md-card>
      </div>
    </form>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import { notify } from "@/mixins/notify"
import { RoleService } from "@/services/RoleService"
export default {
  name: "NewUser",
  mixins: [notify],
  components: {
    Widget,
  },
  props: {
    showNewUser: {
      type: Boolean,
      default: false,
    },
    user: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      roleService: new RoleService(),
      selectedRoles: [],
    }
  },
  async mounted() {
    try {
      await this.roleService.fetchAll()
    } catch (e) {
      this.alertNotify("error", "Failed to fetch roles")
      console.error(e)
    }
  },
  methods: {
    async createUser() {
      const validation = await this.$validator.validateAll("Create-Form")
      if (!validation) {
        return
      }

      // Prevent creating users with no roles
      if (!this.selectedRoles || this.selectedRoles.length === 0) {
        this.$notify({
          group: "notify",
          type: "error",
          title: "Validation Error",
          text: "Users must have at least one role assigned.",
        })
        return
      }

      this.$emit("createUser", { roles: this.selectedRoles })
    },
    closeNewUser() {
      this.$emit("newUserClosed")
    },
  },
}
</script>

<style scoped></style>
