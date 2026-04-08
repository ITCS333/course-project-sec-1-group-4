// --- Global Data Store ---
let users = [];

// --- Element Selections ---
const userTableBody = document.getElementById("user-table-body");
const addUserForm = document.getElementById("add-user-form");
const changePasswordForm = document.getElementById("password-form");
const searchInput = document.getElementById("search-input");
const tableHeaders = document.querySelectorAll("#user-table thead th");

// --- Functions ---

function createUserRow(user) {
  const tr = document.createElement("tr");

  const nameTd = document.createElement("td");
  nameTd.textContent = user.name;

  const emailTd = document.createElement("td");
  emailTd.textContent = user.email;

  const adminTd = document.createElement("td");
  adminTd.textContent = user.is_admin == 1 ? "Yes" : "No";

  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = user.id;

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("delete-btn");
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
  userTableBody.innerHTML = "";

  userArray.forEach(user => {
    const row = createUserRow(user);
    userTableBody.appendChild(row);
  });
}

async function handleChangePassword(event) {
  event.preventDefault();

  const currentPassword = document.getElementById("current-password").value;
  const newPassword = document.getElementById("new-password").value;
  const confirmPassword = document.getElementById("confirm-password").value;

  if (newPassword !== confirmPassword) {
    alert("Passwords do not match.");
    return;
  }

  if (newPassword.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  const id = 1;

  const response = await fetch("../common/index.php?action=change_password", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      id,
      current_password: currentPassword,
      new_password: newPassword
    })
  });

  const result = await response.json();

  if (result.success) {
    alert("Password updated successfully!");
    changePasswordForm.reset();
  } else {
    alert(result.message);
  }
}

async function handleAddUser(event) {
  event.preventDefault();

  const name = document.getElementById("user-name").value;
  const email = document.getElementById("user-email").value;
  const password = document.getElementById("default-password").value;
  const is_admin = document.getElementById("is-admin").value;

  if (!name || !email || !password) {
    alert("Please fill out all required fields.");
    return;
  }

  if (password.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  const response = await fetch("../common/index.php", {
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
    loadUsersAndInitialize();
    addUserForm.reset();
  } else {
    const result = await response.json();
    alert(result.message);
  }
}

async function handleTableClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const id = event.target.dataset.id;

    const response = await fetch("../common/index.php?id=" + id, {
      method: "DELETE"
    });

    const result = await response.json();

    if (result.success) {
      users = users.filter(user => user.id != id);
      renderTable(users);
    } else {
      alert(result.message);
    }
  }

  if (event.target.classList.contains("edit-btn")) {
    const id = event.target.dataset.id;

    const user = users.find(u => u.id == id);

    const newName = prompt("Enter new name:", user.name);

    if (!newName) return;

    const response = await fetch("../common/index.php", {
      method: "PUT",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        id,
        name: newName
      })
    });

    const result = await response.json();

    if (result.success) {
      loadUsersAndInitialize();
    } else {
      alert(result.message);
    }
  }
}

function handleSearch() {
  const term = searchInput.value.toLowerCase();

  if (!term) {
    renderTable(users);
    return;
  }

  const filtered = users.filter(user =>
    user.name.toLowerCase().includes(term) ||
    user.email.toLowerCase().includes(term)
  );

  renderTable(filtered);
}

function handleSort(event) {
  const index = event.currentTarget.cellIndex;

  const mapping = ["name", "email", "is_admin"];
  const key = mapping[index];

  if (!key) return;

  const dir = event.currentTarget.dataset.sortDir === "asc" ? "desc" : "asc";

  event.currentTarget.dataset.sortDir = dir;

  users.sort((a, b) => {
    let result;

    if (key === "is_admin") {
      result = a[key] - b[key];
    } else {
      result = a[key].localeCompare(b[key]);
    }

    return dir === "asc" ? result : -result;
  });

  renderTable(users);
}

async function loadUsersAndInitialize() {
  const response = await fetch("../common/index.php");

  if (!response.ok) {
    alert("Failed to load users.");
    return;
  }

  const result = await response.json();

  users = result.data;

  renderTable(users);

  changePasswordForm.addEventListener("submit", handleChangePassword);
  addUserForm.addEventListener("submit", handleAddUser);
  userTableBody.addEventListener("click", handleTableClick);
  searchInput.addEventListener("input", handleSearch);

  tableHeaders.forEach(th =>
    th.addEventListener("click", handleSort)
  );
}

loadUsersAndInitialize();
