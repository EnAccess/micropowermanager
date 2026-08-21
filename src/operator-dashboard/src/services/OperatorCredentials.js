const STORAGE_KEY = "mpm.operator.credentials"

/**
 * Basic auth means a reversible password, so it is held in sessionStorage: scoped
 * to the tab and gone when the browser closes. The backend credential is expected
 * to be a dedicated operator service account, never a person's own login.
 */
export const OperatorCredentials = {
  read() {
    return window.sessionStorage.getItem(STORAGE_KEY)
  },

  save(username, password) {
    window.sessionStorage.setItem(
      STORAGE_KEY,
      window.btoa(`${username}:${password}`),
    )
  },

  clear() {
    window.sessionStorage.removeItem(STORAGE_KEY)
  },

  header() {
    const credentials = this.read()

    return credentials ? `Basic ${credentials}` : null
  },
}
