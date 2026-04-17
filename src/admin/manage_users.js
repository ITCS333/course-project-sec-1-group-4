let users = [];
let initialized = false;


function getEl(id) {
  if (typeof document === "undefined") return null;
  return document.getElementById(id);
}

function getAll(selector) {
  if (typeof document === "undefined") return [];
  return document.querySelectorAll(selector);
}


function createUserRow(user) {
  const tr = document.createElement("tr");

  const nameTd = document.createElement("td");
  nameTd.textContent = user.name;

  const emailTd = document.createElement("td");
  emailTd.textContent = user.email;

  const adminTd = document.createElement("td");
  adminTd.textContent = Number(user.is_admin) === 1 ? "Yes" : "No";

  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.className = "edit-btn";
  editBtn.dataset.id = user.id;

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.className = "delete-btn";
  deleteBtn.dataset.id = user.id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(nameTd);
  tr.appendChild(emailTd);
  tr.appendChild(adminTd);
  tr.appendChild(actionsTd);

  return tr;
}


function renderTable(userArray) {
  const tbody = getEl("user-table-body");
  if (!tbody) return;

  tbody.innerHTML = "";

  userArray.forEach((user) => {
    tbody.appendChild(createUserRow(user));
  });
}


async function handleChangePassword(event) {
  event.preventDefault();

  const currentPassword = getEl("current-password").value;
  const newPassword = getEl("new-password").value;
  const confirmPassword = getEl("confirm-password").value;

  if (newPassword !== confirmPassword) {
    alert("Passwords do not match.");
    return;
  }

  if (newPassword.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  const response = await fetch("../api/index.php?action=change_password", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      id: 1,
      current_password: currentPassword,
      new_password: newPassword
    })
  });

  const result = await response.json();

  if (result.success) {
    alert("Password updated successfully!");
  } else {
    alert(result.message);
  }
}


async function handleAddUser(event) {
  event.preventDefault();

  const name = getEl("user-name").value.trim();
  const email = getEl("user-email").value.trim();
  const password = getEl("default-password").value;
  const is_admin = getEl("is-admin").value;

  if (!name || !email || !password) {
    alert("Missing fields");
    return;
  }

  if (password.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  const response = await fetch("../api/index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      name,
      email,
      password,
      is_admin
    })
  });

  if (response.status === 201) {
    await loadUsersAndInitialize();
  }
}


async function handleTableClick(event) {
  const target = event.target;

  if (target.classList.contains("delete-btn")) {
    const id = target.dataset.id;

    const response = await fetch(
      "../api/index.php?id=" + id,
      { method: "DELETE" }
    );

    const result = await response.json();

    if (result.success) {
      users = users.filter((u) => String(u.id) !== String(id));
      renderTable(users);
    }
  }

  if (target.classList.contains("edit-btn")) {
    const id = target.dataset.id;

    const newName = prompt("Enter new name:");

    if (!newName) return;

    await fetch("../api/index.php", {
      method: "PUT",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        id,
        name: newName
      })
    });

    await loadUsersAndInitialize();
  }
}


function handleSearch(event) {
  const term = event.target.value.toLowerCase();

  if (!term) {
    renderTable(users);
    return;
  }

  const filtered = users.filter(
    (u) =>
      u.name.toLowerCase().includes(term) ||
      u.email.toLowerCase().includes(term)
  );

  renderTable(filtered);
}


function handleSort(event) {
  const index = event.currentTarget.cellIndex;

  const map = {
    0: "name",
    1: "email",
    2: "is_admin"
  };

  const key = map[index];
  if (!key) return;

  users.sort((a, b) => {
    if (key === "is_admin") {
      return Number(a[key]) - Number(b[key]);
    }
    return a[key].localeCompare(b[key]);
  });

  renderTable(users);
}


async function loadUsersAndInitialize() {
  const response = await fetch("../api/index.php");

  if (!response.ok) return;

  const result = await response.json();

  users = result.data;

  renderTable(users);

  if (!initialized && typeof document !== "undefined") {
    const passwordForm = getEl("password-form");
    const addUserForm = getEl("add-user-form");
    const tbody = getEl("user-table-body");
    const searchInput = getEl("search-input");
    const headers = getAll("#user-table thead th");

    passwordForm?.addEventListener("submit", handleChangePassword);
    addUserForm?.addEventListener("submit", handleAddUser);
    tbody?.addEventListener("click", handleTableClick);
    searchInput?.addEventListener("input", handleSearch);
    headers.forEach((h) => h.addEventListener("click", handleSort));

    initialized = true;
  }
}


if (typeof window !== "undefined") {
  loadUsersAndInitialize();
}

if (typeof module !== "undefined") {
  module.exports = {
    createUserRow,
    renderTable,
    handleChangePassword,
    handleAddUser,
    handleTableClick,
    handleSearch,
    handleSort,
    loadUsersAndInitialize
  };
}
