<?php
interface Observer {
    public function update($subject, $status);
    public function getId();
    public function getName();
    public function getEmail();
}
