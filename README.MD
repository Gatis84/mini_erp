- [ Read in Latvian language](README.lv.md)

# Work management module (Yii2 Advanced + RBAC custom + Gii for templates + MSSQL + Docker)

This repository contains a work management system with 3 modules (Employees, Construction Sites, Work Tasks) and an API for the pass system depending on the Employee/Construction site access level. The project is built with **Yii2** and uses **MSSQL** database. The entire launch is planned **via Docker**.

---

## Technologies

- PHP (Docker container - php:8.2-fpm-bullseye)
- Nginx (reverse proxy / web server)
- Yii 2 Framework, advanced template selected for seperate Design Frontend/Backend/Common ..
- MSSQL (Microsoft SQL Server 2022 - latest) 
- Composer
- Docker + Docker Compose
- Gii for view templates
- RBAC modified/adapted
- ..

---

## Project functionality (requirements summary)

### Backend **sysAdmin** : Employees/Construction site/Tasks CRUD + user delete/restore

### 1) Employee management module
**Employee with User account**: first name, last name, date of birth, Email, access level, role, status (active/inactive/deleted). TODO: User can have more then one role as employee.

Role permissions:
- **SysAdmin (admin)**: add/edit/deactivate/activate employees + Delete/Restore employees User account in Backend. Can`t DELETE self by RBAC rule.
- **Admin (admin)**: add/edit/deactivate/activate employees in FrontEnd. Self and other Admins excluded by RBAC rules, can only view their profile!
- **Manager (teamLead)**: An employee with a teamLead role can see their subordinates.
- **Worker (employee)**: An employee without additional roles can see information about themselves.

### 2) Construction site module
**Construction Site**: A building object consists of information about its location, square footage, and required access level, seleceted Available Team Leads.

Role permissions:
- **SysAdmin (admin)**: Construction Site Full CRUD.
- **Admin (admin)**: An employee with the admin role can add/edit/delete construction sites, add/edit/delete work tasks related to a construction site.
- **Manager (teamLead)**: An employee with the teamLead role can see the construction projects they manage and the list of available tasks in them.
- **Worker (employee)**: The employee can see the construction sites where work has been planned/performed for him/her.

### 3) Tasks module
**Task**: A Task contains information about which construction project, which employee, and when specific work must be performed. Have info fields like: Construction Site ID, title, Description, Assigned Employees, status(draft, active, cancelled, archived), Created_by, Created At, Updated At, Planned Start At, Planned End At, Completed At.

Role permissions:
- **Admin (admin)**: An employee with the admin role can add/edit/delete jobs, change task status, set start/end/completed datetime, assign employees.
- **Manager (teamLead)**: An employee with the teamLead role can add/edit/delete work tasks on construction sites only under their management. Can change task assignment status (Assigned, in Progress, Completed, Overdue, Canceled) assigned to them.
- **Worker (employee)**: An employee without additional roles can see the tasks assigned to them. Can change own task assignment status (Assigned, in Progress, Completed, Overdue, Canceled) assigned to them.

### 4) API for pass system
The API validates whether an employee has the right to access a specific construction site on a given date, based on **assigned tasks** (and access level requirement). An employee has access to a construction site on date D if:
    - he has a task assignment
    - the task belongs to construction_site
    - D is between planned_start_at and planned_end_at
    - the employee's access level ≥ required access level
    - TeamLead/employee can access if he is assigned to the construction site OR has a task on the site
    - or the employee is an SysAdmin & Admin

---

## Launching with Docker

### Requirements
- Docker
- Docker Compose

### Starting
* The PHP container uses a startup script (bash docker/php/init.sh), which ensures that the system is immediately usable after docker compose up, without manual commands. TRUNCATE is turned on, i.e. old data is deleted at each container startup.

* seeders create test data automatically with the help of faker (docker).
  Generated Data Count can be changed here: \docker\php\init.sh.
* Backend login: sysAdmin pw: admin123
* fake users logins: username-id, password: user123+id (john.doe-**99**,pw: user123**99**)

```bash
docker compose up -d --build
```
Then:

* UI available: Frontend http://localhost:8080 & for admins backend http://localhost:8080/admin

* DB created automatically

* migrations already completed

* RBAC initialized

* Admin user created

* test data created
   
## Application user interface (UI)

### Login Frontend – Employee, Construction Site and Task Management
![Frontend Login](docs/screenshots/frontend_index.png)

### Backend Admin Panel – User, Employee, Construction Site and Task management
![Backend index](docs/screenshots/backend_index.png)

### FE Employee login
![Employee login](docs/screenshots/FE_login01.png)

### FE Employees list
![Employees list](docs/screenshots/FE_employees.png)

### FE Employee view
![Employees view](docs/screenshots/FE_employee_view.png)

### FE Employee Edit
![Employees update](docs/screenshots/FE_employee_update.png)

### FE Construction Sites list
![Construction sites](docs/screenshots/FE_construction_sites.png)

### FE Construction Site view
![Construction sites](docs/screenshots/FE_construction_site_view.png)

### FE Construction Site edit
![Construction sites](docs/screenshots/FE_construction_site_edit.png)


### FE Tasks list
![Tasks list](docs/screenshots/FE_tasks_list.png)

### FE Task view
![Task view](docs/screenshots/FE_task_view.png)

### FE Own Task view with option of task assignment status change
![Tasks view own](docs/screenshots/FE_task_view_own.png)

### FE Task edit
![Task edit](docs/screenshots/FE_task_edit.png)

### BE Users list
![Users list ](docs/screenshots/BE_users.png)

### BE Users view
![User view ](docs/screenshots/BE_user_view.png)

### BE Employees list
![Employees list](docs/screenshots/BE_employees.png)

### BE Employee view
![Employee view](docs/screenshots/BE_employee_view.png)

### BE Employee Edit
![Employee edit](docs/screenshots/BE_employee_edit.png)

### BE Construction sites list
![Construction sites list ](docs/screenshots/BE_construction_sites.png)

### BE Construction site view
![Construction site view ](docs/screenshots/BE_construction_site_view.png)

### BE Construction site edit
![Construction site edit ](docs/screenshots/BE_construction_site_update.png)

### BE Tasks list
![Tasks list ](docs/screenshots/BE_tasks_list.png)

### BE Task view
![Task view](docs/screenshots/BE_task_view.png)

### BE Task edit
![Task edit](docs/screenshots/BE_task_update.png)


TODO: 
- Visual improvements to have a unified style + a consistent modal window for popup windows
- New Role with permissions add from Backend sysAdmin
- Tests
- API V2 (Full CRUD)
- improve Documentation / Function Annotation
- Fixed position column names/table header when scrolling list
- Signup fix (Need to adjust to employee model). It`s already possible, but for now there is little hack(need to delete and then restore user from Backend with sysadmin user permissions, because restore includes basic fields needed for employee).
- Add option to add task from FE Construction site view, also add Tasks, Construction sites Assignments for easier use (like it is now in Backend Admin views)